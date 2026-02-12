<?php

namespace App\Service;

use App\DTO\TransferRequest;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Exception\AccountNotFoundException;
use App\Exception\InsufficientFundsException;
use App\Exception\InvalidTransferException;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class FundTransferService
{
    private const LOCK_TTL = 30; // seconds

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountRepository $accountRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Transfer funds between accounts with distributed locking
     */
    public function transfer(TransferRequest $request): Transaction
    {
        // Validate same account transfer
        if ($request->getFromAccountNumber() === $request->getToAccountNumber()) {
            throw new InvalidTransferException('Cannot transfer to the same account');
        }

        // Validate amount
        if (bccomp($request->getAmount(), '0', 2) <= 0) {
            throw new InvalidTransferException('Transfer amount must be positive');
        }

        // Create locks for both accounts (in consistent order to prevent deadlocks)
        $accountNumbers = [
            $request->getFromAccountNumber(),
            $request->getToAccountNumber()
        ];
        sort($accountNumbers);

        $locks = [];
        foreach ($accountNumbers as $accountNumber) {
            $lock = $this->lockFactory->createLock('account_transfer_' . $accountNumber, self::LOCK_TTL);
            if (!$lock->acquire()) {
                $this->releaseLocks($locks);
                throw new \RuntimeException('Unable to acquire lock for account: ' . $accountNumber);
            }
            $locks[] = $lock;
        }

        try {
            $transaction = $this->executeTransfer($request);
            $this->releaseLocks($locks);
            return $transaction;
        } catch (\Throwable $e) {
            $this->releaseLocks($locks);
            throw $e;
        }
    }

    private function executeTransfer(TransferRequest $request): Transaction
    {
        return $this->entityManager->wrapInTransaction(function () use ($request) {
            // Fetch accounts with pessimistic locking
            $fromAccount = $this->accountRepository->findByAccountNumberWithLock(
                $request->getFromAccountNumber()
            );

            if (!$fromAccount) {
                throw new AccountNotFoundException($request->getFromAccountNumber());
            }

            $toAccount = $this->accountRepository->findByAccountNumberWithLock(
                $request->getToAccountNumber()
            );

            if (!$toAccount) {
                throw new AccountNotFoundException($request->getToAccountNumber());
            }

            // Validate currency match
            if ($fromAccount->getCurrency() !== $toAccount->getCurrency()) {
                throw new InvalidTransferException(
                    sprintf(
                        'Currency mismatch: %s vs %s',
                        $fromAccount->getCurrency(),
                        $toAccount->getCurrency()
                    )
                );
            }

            // Check sufficient funds
            if (!$fromAccount->hasMinimumBalance($request->getAmount())) {
                throw new InsufficientFundsException(
                    $fromAccount->getAccountNumber(),
                    $request->getAmount(),
                    $fromAccount->getBalance()
                );
            }

            // Create transaction record
            $transaction = new Transaction();
            $transaction->setFromAccount($fromAccount);
            $transaction->setToAccount($toAccount);
            $transaction->setAmount($request->getAmount());
            $transaction->setCurrency($fromAccount->getCurrency());
            $transaction->setDescription($request->getDescription());

            try {
                // Perform the transfer
                $fromAccount->debit($request->getAmount());
                $toAccount->credit($request->getAmount());

                // Mark transaction as completed
                $transaction->markAsCompleted();

                // Persist all changes
                $this->accountRepository->save($fromAccount);
                $this->accountRepository->save($toAccount);
                $this->transactionRepository->save($transaction);

                $this->entityManager->flush();

                $this->logger->info('Fund transfer completed', [
                    'transaction_id' => $transaction->getTransactionId(),
                    'from' => $fromAccount->getAccountNumber(),
                    'to' => $toAccount->getAccountNumber(),
                    'amount' => $request->getAmount(),
                    'currency' => $fromAccount->getCurrency()
                ]);

                return $transaction;
            } catch (\Throwable $e) {
                $transaction->markAsFailed($e->getMessage());
                $this->transactionRepository->save($transaction);
                $this->entityManager->flush();

                $this->logger->error('Fund transfer failed', [
                    'transaction_id' => $transaction->getTransactionId(),
                    'error' => $e->getMessage(),
                    'from' => $fromAccount->getAccountNumber(),
                    'to' => $toAccount->getAccountNumber(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction(string $transactionId): ?Transaction
    {
        return $this->transactionRepository->findByTransactionId($transactionId);
    }

    /**
     * Get account transactions
     */
    public function getAccountTransactions(string $accountNumber, int $limit = 50): array
    {
        return $this->transactionRepository->findByAccountNumber($accountNumber, $limit);
    }

    /**
     * Release all locks
     */
    private function releaseLocks(array $locks): void
    {
        /** @var LockInterface $lock */
        foreach ($locks as $lock) {
            $lock->release();
        }
    }
}
