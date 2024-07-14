<?php

namespace SmtpEmailValidatorBundle\Service;

use SmtpValidatorEmail\Helper\ValidationHelper;

interface SmtpValidatorInterface
{
    /**
     * @see ValidationHelper::catchAll()
     */
    public const OPTION_CATCH_ALL_IS_VALID = "catchAllIsValid";
    public const OPTION_CATCH_ALL_ENABLED  = "catchAllEnabled";

    /**
     * Will validate given E-Mail
     *
     * @param array $emails
     *
     * @return Array<string, bool> - where <string> is E-Mail address, and <bool> indicates if given E-Mail exists or not
     *                               -> true  = exists
     *                               -> false = does not exists
     */
    public function validateEmail(array $emails): array;
}