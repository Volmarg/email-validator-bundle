<?php

namespace SmtpEmailValidatorBundle\Service;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;
use Psr\Log\LoggerInterface;
use SmtpEmailValidatorBundle\Service\Cli\Curl;
use SmtpEmailValidatorBundle\Service\Domain\DomainService;
use Throwable;
use TypeError;

/**
 * Handles E-Mail validation via SMTP
 */
class SmtpValidator extends AbstractSmtpValidator implements SmtpValidatorInterface
{
    /**
     * Reduces time of validations, as it may happen that the same E-Mail gets validated more than once
     * and some calls are expensive
     *
     * @var array (key is the E-Mail Address, value is `isValid` state),
     */
    private array $alreadyValidatedEmails = [];

    public function __construct(
        private LoggerInterface $smtpValidatorLogger,
    ) {
    }

    /**
     * {@inheritDoc}
     * It loops over each E-Mail instead of checking them all to prevent error: "MAIL FROM not accepted"
     */
    public function validateEmail(array $emails, bool $logFailedValidation = true): array
    {

        try {
            $emailsCheckResults = [];

            foreach ($emails as $email) {

                if ($this->isAlreadyValidated($email)) {
                    $emailsCheckResults[$email] = $this->getAlreadyValidatedEmailStatus($email);
                    continue;
                }

                if (!$this->doBaseValidation($email, $logFailedValidation)) {
                    $emailsCheckResults[$email] = false;
                    $this->addEmailToValidated($email, false);
                    continue;
                }
                $validator           = new EmailValidator();
                $multipleValidations = new MultipleValidationWithAnd([
                    new RFCValidation(),
                    new DNSCheckValidation()
                ]);

                $emailsCheckResults[$email] = $validator->isValid($email, $multipleValidations);

                $errors   = [];
                $warnings = [];
                foreach ($validator->getWarnings() as $warning) {
                    $warnings[] = $warning->message();
                }

                if (!is_null($validator->getError())) {
                    $errors[] = $validator->getError()->reason()->description();
                }

                if (!empty($errors) || !empty($warnings)) {
                    $this->smtpValidatorLogger->warning("[{$email}] E-mail is invalid: ", [
                        'errors'   => $errors,
                        'warnings' => $warnings,
                    ]);
                }

            }

        } catch (Exception|TypeError $e) {
            $this->handleValidationException($e);

            return [];
        }

        return $emailsCheckResults;
    }

    /**
     * Will handle the validation exception
     *
     * @param Throwable $e
     *
     * @return void
     */
    private function handleValidationException(Throwable $e): void
    {
        /**
         * Know issues that can be ignored:
         * 1) DDOS Exceptions etc. can be thrown - this information is not needed, skipping it (it's just SMTP side denying connections
         * 2) "stream_set_timeout(): supplied resource is not a valid stream resource" which can mean that:
         *   - connection get rejected,
         *   - server response is slow or there is none at all
         *   - {@link https://www.php.net/manual/en/function.stream-set-timeout.php}
         */
        $this->smtpValidatorLogger->warning("Could not validate E-Mail via SMTP", [
            "exception" => [
                "message" => $e->getMessage(),
                "trace"   => $e->getTraceAsString(),
                "class"   => $e::class,
            ]
        ]);
    }

    /**
     * Will handle some basic email validation, which is faster than smtp and might help to save some time,
     * also SMTP validation is not perfect, as there are some cases where domain does not exist but smtp validation
     * says that email is valid
     *
     * @param string $emailAddress
     * @param bool   $logFailedValidation
     *
     * @return bool
     */
    public function doBaseValidation(string $emailAddress, bool $logFailedValidation = true): bool
    {
        if ($this->isAlreadyValidated($emailAddress)) {
            return $this->getAlreadyValidatedEmailStatus($emailAddress);
        }

        if (!$this->validateBySyntax($emailAddress)) {
            if ($logFailedValidation) {
                $this->smtpValidatorLogger->warning("[{$emailAddress}] This is not synthetically valid email: {$emailAddress}");
            }

            $this->addEmailToValidated($emailAddress, false);
            return false;
        }

        if (!$this->validateByDomain($emailAddress)) {

            if ($logFailedValidation) {
                $this->smtpValidatorLogger->warning("[{$emailAddress}] This E-mail is not valid. Host is not reachable.");
            }

            $this->addEmailToValidated($emailAddress, false);
            return false;
        }

        $this->addEmailToValidated($emailAddress, true);
        return true;
    }

    /**
     * Will validate E-Mail by its domain
     *
     * @param string $emailAddress
     *
     * @return bool
     */
    private function validateByDomain(string $emailAddress): bool
    {
        $domain = DomainService::extractFromEmail($emailAddress);
        return Curl::isValidDomain($domain);
    }

    /**
     * Will validate the email by its syntax
     *
     * @param string $emailAddress
     *
     * @return bool
     */
    private function validateBySyntax(string $emailAddress): bool
    {
        return filter_var($emailAddress, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if E-Mail address is already validated
     *
     * @param string $emailAddress
     *
     * @return bool
     */
    private function isAlreadyValidated(string $emailAddress): bool
    {
        return array_key_exists($emailAddress, $this->alreadyValidatedEmails);
    }

    /**
     * Will return validation status for E-mail,
     * - bool (if validation was made before_,
     * - null (if no such E-Mail was validated before)
     *
     * @param string $emailAddress
     *
     * @return bool|null
     */
    private function getAlreadyValidatedEmailStatus(string $emailAddress): ?bool
    {
        return $this->alreadyValidatedEmails[$emailAddress] ?? null;
    }

    /**
     * Will add the given E-Mail address to the pool of already validated E-Mails
     *
     * @param string $emailAddress
     * @param bool   $status
     *
     * @return void
     */
    private function addEmailToValidated(string $emailAddress, bool $status): void
    {
        $this->alreadyValidatedEmails[$emailAddress] = $status;
    }
}