<?php

namespace Jurager\Passport\Exceptions;

use Exception;

class CustomProviderException extends Exception
{
    public function __construct()
    {
        parent::__construct('Choose a valid IP address lookup provider. The class must implement the Jurager\Passport\Interfaces\Provider interface.');
    }
}