<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113233646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove email verification fields from customer table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP verification_code, DROP is_verified, DROP verification_code_expires_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer ADD verification_code VARCHAR(6) DEFAULT NULL, ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, ADD verification_code_expires_at DATETIME DEFAULT NULL');
    }
}
