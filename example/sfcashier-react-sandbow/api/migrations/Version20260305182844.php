<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305182844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, product_id INTEGER NOT NULL, product_name VARCHAR(255) NOT NULL, product_slug VARCHAR(255) NOT NULL, product_description CLOB DEFAULT NULL, product_image_url VARCHAR(512) DEFAULT NULL, unit_price INTEGER NOT NULL, quantity INTEGER NOT NULL, subtotal INTEGER NOT NULL, order_id INTEGER NOT NULL, CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('ALTER TABLE "order" ADD COLUMN stripe_checkout_session_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD COLUMN stripe_payment_intent_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE order_item');
        $this->addSql('CREATE TEMPORARY TABLE __temp__order AS SELECT id, order_number, total, status, created_at, user_id FROM "order"');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, order_number VARCHAR(255) NOT NULL, total INTEGER NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "order" (id, order_number, total, status, created_at, user_id) SELECT id, order_number, total, status, created_at, user_id FROM __temp__order');
        $this->addSql('DROP TABLE __temp__order');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398551F0F81 ON "order" (order_number)');
        $this->addSql('CREATE INDEX IDX_F5299398A76ED395 ON "order" (user_id)');
    }
}
