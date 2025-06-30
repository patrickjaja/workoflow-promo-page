<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630153344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE incoming_connection ADD ms_app_id LONGTEXT DEFAULT NULL, ADD ms_app_password LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incoming_connection RENAME INDEX idx_incoming_connection_user TO IDX_13AAD47DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration RENAME INDEX idx_service_integration_incoming_connection TO IDX_31734B37E93F74CD
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE incoming_connection DROP ms_app_id, DROP ms_app_password, CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE incoming_connection RENAME INDEX idx_13aad47da76ed395 TO IDX_incoming_connection_user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration RENAME INDEX idx_31734b37e93f74cd TO IDX_service_integration_incoming_connection
        SQL);
    }
}
