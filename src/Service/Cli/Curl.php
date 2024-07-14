<?php

namespace SmtpEmailValidatorBundle\Service\Cli;

use UnexpectedValueException;

/**
 * Using shell based `curl` instead of `php based curl` as this one is faster
 */
class Curl
{
    /**
     * Will check if given domain is valid by checking the returned http code.
     * Info:
     *  - domain can return 30x if there is some redirect to main page etc
     *
     *
     * @param string $domain
     *
     * @return bool
     */
    public static function isValidDomain(string $domain): bool
    {
        $httpCode = self::getHttpCode($domain);

        return (
                $httpCode >= 200
            &&  $httpCode < 400
        );
    }

    /**
     * Return the http code for given domain
     *
     * @param string $domain
     *
     * @return int
     */
    public static function getHttpCode(string $domain): int
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            throw new UnexpectedValueException("This is not a valid domain: {$domain}");
        }

        $httpCode = (int)shell_exec("curl -I --write-out %{http_code} --silent --output /dev/null {$domain}");

        return $httpCode;
    }
}