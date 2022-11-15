<?php

namespace Jurager\Passport\Exceptions;

use Exception;

class ProviderException extends Exception
{
    public function __construct()
    {
        parent::__construct('Choose a supported IP address lookup provider.');
    }
}