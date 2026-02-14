<?php

namespace Jurager\Passport\Exceptions;

use RuntimeException;

class RedirectLoopException extends RuntimeException
{
    /**
     * Create a new redirect loop exception instance.
     *
     * @param string $type Type of redirect loop (attach or auth)
     * @param int $attempts Number of attempts made
     */
    public function __construct(string $type = 'SSO', int $attempts = 0)
    {
        $message = sprintf(
            '%s redirect loop detected after %d attempts. Please check your SSO configuration.',
            $type,
            $attempts
        );

        parent::__construct($message, 500);
    }
}
