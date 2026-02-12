<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250211000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed initial test accounts';
    }

    public function up(Schema $schema): void
    {
        $now = date('Y-m-d H:i:s');

        // Insert test accounts
        $this->addSql("INSERT INTO accounts (account_number, account_holder, balance, currency, is_active, created_at, updated_at, version) VALUES
            ('ACC1000000001', 'Aakash Patel', 10000.00, 'INR', 1, '$now', '$now', 1),
            ('ACC1000000002', 'Ashish Bhatt ', 5000.00, 'INR', 1, '$now', '$now', 1),
            ('ACC1000000003', 'Bhavin Garg', 15000.50, 'INR', 1, '$now', '$now', 1),
            ('ACC1000000004', 'Divyesh Mehta', 7500.00, 'INR', 1, '$now', '$now', 1),
            ('ACC1000000005', 'Gaurav Shah', 0.00, 'INR', 1, '$now', '$now', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM accounts WHERE account_number IN ('ACC1000000001', 'ACC1000000002', 'ACC1000000003', 'ACC1000000004', 'ACC1000000005')");
    }
}
