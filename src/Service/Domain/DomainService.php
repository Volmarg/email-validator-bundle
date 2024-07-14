<?php

namespace SmtpEmailValidatorBundle\Service\Domain;

use UnexpectedValueException;

/**
 * Provides variety of functionality for handling domain related logic
 */
class DomainService
{
    /**
     * Will extract the domain from provided email address
     *
     * @param string $emailAddress
     *
     * @return string
     */
    public static function extractFromEmail(string $emailAddress): string
    {
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new UnexpectedValueException("This is not valid Email: {$emailAddress}");
        }

        $emailPartials = explode("@", $emailAddress);
        $domain        = array_pop($emailPartials);

        return $domain;
    }

}