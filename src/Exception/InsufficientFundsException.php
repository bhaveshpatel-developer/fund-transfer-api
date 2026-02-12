<?php

namespace App\Exception;

class InsufficientFundsException extends \Exception
{
    public function __construct(string $accountNumber, string $required, string $available)
    {
        parent::__construct(
            sprintf(
                'Insufficient funds in account %s. Required: %s, Available: %s',
                $accountNumber,
                $required,
                $available
            )
        );
    }
}
