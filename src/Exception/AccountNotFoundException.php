<?php

namespace App\Exception;

class AccountNotFoundException extends \Exception
{
    public function __construct(string $accountNumber)
    {
        parent::__construct(sprintf('Account not found: %s', $accountNumber));
    }
}
