<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deterministic resource linkage columns to cashier generated invoices';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cashier_generated_invoices ADD COLUMN resource_type VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE cashier_generated_invoices ADD COLUMN resource_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cashier_generated_invoices ADD COLUMN plan_code VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__cashier_generated_invoices AS SELECT id, billable_id, billable_type, stripe_invoice_id, stripe_payment_intent_id, stripe_checkout_session_id, currency, amount_total, status, filename, relative_path, mime_type, size, checksum, payload, created_at, updated_at, customer_id FROM cashier_generated_invoices');
        $this->addSql('DROP TABLE cashier_generated_invoices');
        $this->addSql('CREATE TABLE cashier_generated_invoices (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, billable_id INTEGER DEFAULT NULL, billable_type VARCHAR(255) DEFAULT NULL, stripe_invoice_id VARCHAR(255) DEFAULT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, stripe_checkout_session_id VARCHAR(255) DEFAULT NULL, currency VARCHAR(10) NOT NULL, amount_total INTEGER NOT NULL, status VARCHAR(50) NOT NULL, filename VARCHAR(255) NOT NULL, relative_path VARCHAR(500) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INTEGER NOT NULL, checksum VARCHAR(64) NOT NULL, payload CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, customer_id INTEGER DEFAULT NULL, CONSTRAINT FK_1729E2D69395C3F3 FOREIGN KEY (customer_id) REFERENCES cashier_customers (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO cashier_generated_invoices (id, billable_id, billable_type, stripe_invoice_id, stripe_payment_intent_id, stripe_checkout_session_id, currency, amount_total, status, filename, relative_path, mime_type, size, checksum, payload, created_at, updated_at, customer_id) SELECT id, billable_id, billable_type, stripe_invoice_id, stripe_payment_intent_id, stripe_checkout_session_id, currency, amount_total, status, filename, relative_path, mime_type, size, checksum, payload, created_at, updated_at, customer_id FROM __temp__cashier_generated_invoices');
        $this->addSql('DROP TABLE __temp__cashier_generated_invoices');
        $this->addSql('CREATE INDEX IDX_1729E2D69395C3F3 ON cashier_generated_invoices (customer_id)');
        $this->addSql('CREATE UNIQUE INDEX cashier_generated_invoice_stripe_invoice_unique ON cashier_generated_invoices (stripe_invoice_id)');
    }
}
