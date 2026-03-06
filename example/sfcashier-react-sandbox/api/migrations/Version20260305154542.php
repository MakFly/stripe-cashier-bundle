<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305154542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE refresh_tokens (token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, revoked BOOLEAN NOT NULL, replaced_by VARCHAR(255) DEFAULT NULL, user_id INTEGER NOT NULL, PRIMARY KEY (token))');
        $this->addSql('CREATE TABLE sessions (token VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, ip_address VARCHAR(45) NOT NULL, user_agent CLOB NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, metadata CLOB DEFAULT NULL, user_id INTEGER NOT NULL, PRIMARY KEY (token))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE sessions');
    }
}
