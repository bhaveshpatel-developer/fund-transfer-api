<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function findByAccountNumber(string $accountNumber): ?Account
    {
        return $this->findOneBy(['accountNumber' => $accountNumber]);
    }

    public function findActiveByAccountNumber(string $accountNumber): ?Account
    {
        return $this->findOneBy([
            'accountNumber' => $accountNumber,
            'isActive' => true
        ]);
    }

    /**
     * Find account with pessimistic write lock for transaction safety
     */
    public function findByAccountNumberWithLock(string $accountNumber): ?Account
    {
        return $this->createQueryBuilder('a')
            ->where('a.accountNumber = :accountNumber')
            ->andWhere('a.isActive = :isActive')
            ->setParameter('accountNumber', $accountNumber)
            ->setParameter('isActive', true)
            ->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function save(Account $account, bool $flush = false): void
    {
        $this->getEntityManager()->persist($account);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
