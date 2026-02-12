<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TransferRequest
{
    #[Assert\NotBlank(message: 'From account number is required')]
    #[Assert\Length(min: 10, max: 50)]
    private ?string $fromAccountNumber = null;

    #[Assert\NotBlank(message: 'To account number is required')]
    #[Assert\Length(min: 10, max: 50)]
    private ?string $toAccountNumber = null;

    #[Assert\NotBlank(message: 'Amount is required')]
    #[Assert\Positive(message: 'Amount must be positive')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'Amount must be a valid decimal number with up to 2 decimal places'
    )]
    private ?string $amount = null;

    #[Assert\Length(max: 500)]
    private ?string $description = null;

    public function getFromAccountNumber(): ?string
    {
        return $this->fromAccountNumber;
    }

    public function setFromAccountNumber(?string $fromAccountNumber): void
    {
        $this->fromAccountNumber = $fromAccountNumber;
    }

    public function getToAccountNumber(): ?string
    {
        return $this->toAccountNumber;
    }

    public function setToAccountNumber(?string $toAccountNumber): void
    {
        $this->toAccountNumber = $toAccountNumber;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): void
    {
        $this->amount = $amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
