<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630134916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Teams ID management and Custom Service support';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE custom_service (id INT AUTO_INCREMENT NOT NULL, base_url VARCHAR(500) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, service_integration_id INT NOT NULL, UNIQUE INDEX UNIQ_E6C4F31F9DE4805C (service_integration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE custom_service_header (id INT AUTO_INCREMENT NOT NULL, header_name VARCHAR(255) NOT NULL, header_value LONGTEXT NOT NULL, custom_service_id INT NOT NULL, INDEX IDX_7A50982DD2A3954C (custom_service_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE teams_id (id INT AUTO_INCREMENT NOT NULL, teams_id VARCHAR(255) NOT NULL, display_name VARCHAR(255) DEFAULT NULL, is_primary TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_D6365F12A76ED395 (user_id), UNIQUE INDEX UNIQ_USER_TEAMS_ID (user_id, teams_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE custom_service ADD CONSTRAINT FK_E6C4F31F9DE4805C FOREIGN KEY (service_integration_id) REFERENCES service_integration (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE custom_service_header ADD CONSTRAINT FK_7A50982DD2A3954C FOREIGN KEY (custom_service_id) REFERENCES custom_service (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams_id ADD CONSTRAINT FK_D6365F12A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        // Preserve existing teams_id data
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration ADD teams_id_id INT DEFAULT NULL
        SQL);
        
        // Migrate existing user teams IDs to the new teams_id table
        $this->addSql(<<<'SQL'
            INSERT INTO teams_id (user_id, teams_id, display_name, is_primary, created_at, updated_at)
            SELECT DISTINCT u.id, u.teams_account_name, u.teams_account_name, 1, NOW(), NOW()
            FROM user u
            WHERE u.teams_account_name IS NOT NULL AND u.teams_account_name != ''
        SQL);
        
        // Update service integrations to reference the new teams_id records
        $this->addSql(<<<'SQL'
            UPDATE service_integration si
            INNER JOIN user u ON si.user_id = u.id
            INNER JOIN teams_id t ON t.user_id = u.id AND t.teams_id = si.teams_id
            SET si.teams_id_id = t.id
            WHERE si.teams_id IS NOT NULL
        SQL);
        
        // Now drop the old column
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration DROP teams_id
        SQL);
        
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration ADD CONSTRAINT FK_31734B378B7A66FA FOREIGN KEY (teams_id_id) REFERENCES teams_id (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_31734B378B7A66FA ON service_integration (teams_id_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE custom_service DROP FOREIGN KEY FK_E6C4F31F9DE4805C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE custom_service_header DROP FOREIGN KEY FK_7A50982DD2A3954C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE teams_id DROP FOREIGN KEY FK_D6365F12A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE custom_service
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE custom_service_header
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE teams_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration DROP FOREIGN KEY FK_31734B378B7A66FA
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_31734B378B7A66FA ON service_integration
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE service_integration ADD teams_id VARCHAR(255) DEFAULT NULL, DROP teams_id_id
        SQL);
    }
}
