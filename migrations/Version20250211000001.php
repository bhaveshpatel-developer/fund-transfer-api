<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250211000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create accounts and transactions tables';
    }

    public function up(Schema $schema): void
    {
        // Create accounts table
        $this->addSql('CREATE TABLE accounts (
            id INT AUTO_INCREMENT NOT NULL,
            account_number VARCHAR(50) NOT NULL,
            account_holder VARCHAR(255) NOT NULL,
            balance NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(3) NOT NULL DEFAULT "INR",
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            version INT NOT NULL DEFAULT 1,
            UNIQUE INDEX UNIQ_CAC89EAC96DDF2C6 (account_number),
            INDEX idx_account_number (account_number),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create transactions table
        $this->addSql('CREATE TABLE transactions (
            id INT AUTO_INCREMENT NOT NULL,
            transaction_id VARCHAR(36) NOT NULL,
            from_account_id INT NOT NULL,
            to_account_id INT NOT NULL,
            amount NUMERIC(15, 2) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            description TEXT DEFAULT NULL,
            failure_reason TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            completed_at DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_EAA81A4C2FC0CB0F (transaction_id),
            INDEX idx_from_account (from_account_id),
            INDEX idx_to_account (to_account_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            INDEX IDX_EAA81A4C78D0C0DB (from_account_id),
            INDEX IDX_EAA81A4CB9327A0A (to_account_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign keys
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C78D0C0DB 
            FOREIGN KEY (from_account_id) REFERENCES accounts (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CB9327A0A 
            FOREIGN KEY (to_account_id) REFERENCES accounts (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C78D0C0DB');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CB9327A0A');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE accounts');
    }
}
