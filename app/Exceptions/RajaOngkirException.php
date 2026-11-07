<?php

namespace App\Exceptions;

use RuntimeException;

class RajaOngkirException extends RuntimeException
{
    private int $httpStatus;

    public function __construct(string $message, int $httpStatus = 502)
    {
        parent::__construct($message);

        $this->httpStatus = $httpStatus;
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
