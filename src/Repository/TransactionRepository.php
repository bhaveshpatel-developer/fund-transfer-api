<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByTransactionId(string $transactionId): ?Transaction
    {
        return $this->findOneBy(['transactionId' => $transactionId]);
    }

    public function findByAccountNumber(string $accountNumber, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.fromAccount', 'fa')
            ->leftJoin('t.toAccount', 'ta')
            ->where('fa.accountNumber = :accountNumber OR ta.accountNumber = :accountNumber')
            ->setParameter('accountNumber', $accountNumber)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(Transaction $transaction, bool $flush = false): void
    {
        $this->getEntityManager()->persist($transaction);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
