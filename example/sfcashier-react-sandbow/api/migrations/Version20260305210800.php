<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305210800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Cashier bundle tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cashier_customers (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, billable_id INTEGER DEFAULT NULL, billable_type VARCHAR(255) DEFAULT NULL, stripe_id VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, currency VARCHAR(10) DEFAULT NULL, balance INTEGER DEFAULT NULL, address CLOB DEFAULT NULL, pm_type VARCHAR(50) DEFAULT NULL, pm_last_four VARCHAR(4) DEFAULT NULL, invoice_prefix VARCHAR(50) DEFAULT NULL, tax_exempt VARCHAR(50) DEFAULT NULL, trial_ends_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX stripe_id_unique ON cashier_customers (stripe_id)');
        $this->addSql('CREATE UNIQUE INDEX billable_lookup_unique ON cashier_customers (billable_type, billable_id)');
        $this->addSql('CREATE TABLE cashier_subscription_items (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, stripe_id VARCHAR(255) NOT NULL, stripe_product VARCHAR(255) NOT NULL, stripe_price VARCHAR(255) NOT NULL, quantity INTEGER DEFAULT NULL, meter_id VARCHAR(255) DEFAULT NULL, meter_event_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subscription_id INTEGER NOT NULL, CONSTRAINT FK_268112C49A1887DC FOREIGN KEY (subscription_id) REFERENCES cashier_subscriptions (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_268112C43F1B1098 ON cashier_subscription_items (stripe_id)');
        $this->addSql('CREATE INDEX IDX_268112C49A1887DC ON cashier_subscription_items (subscription_id)');
        $this->addSql('CREATE INDEX IDX_268112C49A1887DCE06A4B74 ON cashier_subscription_items (subscription_id, stripe_price)');
        $this->addSql('CREATE TABLE cashier_subscriptions (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, stripe_id VARCHAR(255) NOT NULL, stripe_status VARCHAR(50) NOT NULL, stripe_price VARCHAR(255) DEFAULT NULL, quantity INTEGER DEFAULT NULL, trial_ends_at DATETIME DEFAULT NULL, ends_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, customer_id INTEGER NOT NULL, CONSTRAINT FK_BD5FFE4E9395C3F3 FOREIGN KEY (customer_id) REFERENCES cashier_customers (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5FFE4E3F1B1098 ON cashier_subscriptions (stripe_id)');
        $this->addSql('CREATE INDEX IDX_BD5FFE4E9395C3F3 ON cashier_subscriptions (customer_id)');
        $this->addSql('CREATE INDEX IDX_BD5FFE4E9395C3F3D34D1820 ON cashier_subscriptions (customer_id, stripe_status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cashier_subscription_items');
        $this->addSql('DROP TABLE cashier_subscriptions');
        $this->addSql('DROP TABLE cashier_customers');
    }
}
