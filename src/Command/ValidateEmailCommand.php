<?php

namespace SmtpEmailValidatorBundle\Command;

use Exception;
use SmtpEmailValidatorBundle\Service\SmtpValidatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'validate-email',
    description: 'Will check if given E-Mail is reachable',
)]
class ValidateEmailCommand extends Command
{
    private const OPTION_EMAIL = "email";

    protected function configure(): void
    {
        $this
            ->addOption(self::OPTION_EMAIL, null, InputOption::VALUE_REQUIRED, 'E-Mail to be checked')
        ;
    }

    public function __construct(
        private SmtpValidatorInterface $smtpValidator
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $email = $input->getOption(self::OPTION_EMAIL);
        if (empty($email)) {
            throw new Exception("Provide valid E-Mail!");
        }

        $validationResults = $this->smtpValidator->validateEmail([$email]);
        $isValidEmail      = $validationResults[$email];

        if ($isValidEmail) {
            $io->success("E-mail: {$email} EXIST");
        }else{
            $io->error("E-mail: {$email} DOES NOT EXIST");
        }

        return Command::SUCCESS;
    }
}
