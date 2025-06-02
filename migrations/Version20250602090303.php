<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602090303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token ADD confluence_url VARCHAR(255) DEFAULT NULL, ADD confluence_username VARCHAR(255) DEFAULT NULL, ADD confluence_api_token LONGTEXT DEFAULT NULL, ADD jira_url VARCHAR(255) DEFAULT NULL, ADD jira_username VARCHAR(255) DEFAULT NULL, ADD jira_api_token LONGTEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token DROP confluence_url, DROP confluence_username, DROP confluence_api_token, DROP jira_url, DROP jira_username, DROP jira_api_token
        SQL);
    }
}
