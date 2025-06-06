<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606102717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add test users for Puppeteer testing';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        
        // Add test users for Puppeteer testing
        $this->addSql("INSERT INTO user (email, name, avatar, google_id, teams_account_name, organization_name, is_organization_admin, roles, created_at) VALUES 
            ('puppeteer.test1@example.com', 'Puppeteer Test User 1', NULL, 'test-google-id-1', 'TEAMS_ID_001', 'Test Organization', 1, '[]', NOW()),
            ('puppeteer.test2@example.com', 'Puppeteer Test User 2', NULL, 'test-google-id-2', 'TEAMS_ID_002', 'Test Organization', 0, '[]', NOW())
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        
        // Remove test users
        $this->addSql("DELETE FROM user WHERE email IN ('puppeteer.test1@example.com', 'puppeteer.test2@example.com')");
    }
}
