<?php

namespace SmtpEmailValidatorBundle\Service;

use Faker\Factory;

/**
 * Provides common logic for smtp validator
 */
abstract class AbstractSmtpValidator
{
    /**
     * Will generate random E-Mail
     * @return string
     */
    protected function generateEmail(): string
    {
        $faker = Factory::create();

        return $faker->email();
    }
}