<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606092803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ServiceIntegration entity to support multiple integrations per service';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE service_integration (id INT AUTO_INCREMENT NOT NULL, service VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, is_default TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', user_id INT NOT NULL, INDEX IDX_31734B37A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration ADD CONSTRAINT FK_31734B37A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token DROP FOREIGN KEY FK_B6A2DD68A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B6A2DD68A76ED395 ON access_token
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_USER_SERVICE ON access_token
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token DROP service, CHANGE user_id service_integration_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD689DE4805C FOREIGN KEY (service_integration_id) REFERENCES service_integration (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B6A2DD689DE4805C ON access_token (service_integration_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE n8n_environment_variables CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE n8n_environment_variables RENAME INDEX idx_n8n_env_vars_user_id TO IDX_A82FB723A76ED395
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration DROP FOREIGN KEY FK_31734B37A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE service_integration
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token DROP FOREIGN KEY FK_B6A2DD689DE4805C
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B6A2DD689DE4805C ON access_token
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token ADD service VARCHAR(50) NOT NULL, CHANGE service_integration_id user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token ADD CONSTRAINT FK_B6A2DD68A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B6A2DD68A76ED395 ON access_token (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_USER_SERVICE ON access_token (user_id, service)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE n8n_environment_variables CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE n8n_environment_variables RENAME INDEX idx_a82fb723a76ed395 TO IDX_N8N_ENV_VARS_USER_ID
        SQL);
    }
}
