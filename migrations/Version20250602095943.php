<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602095943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE organization_member (id INT AUTO_INCREMENT NOT NULL, organization_name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(20) DEFAULT 'pending' NOT NULL, invitation_token VARCHAR(255) DEFAULT NULL, invitation_expires_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, invited_by_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX IDX_756A2A8DA7B4A7E3 (invited_by_id), INDEX IDX_756A2A8DA76ED395 (user_id), UNIQUE INDEX UNIQ_ORG_EMAIL (organization_name, email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD organization_name VARCHAR(255) DEFAULT NULL, ADD is_organization_admin TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8DA7B4A7E3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE organization_member
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP organization_name, DROP is_organization_admin
        SQL);
    }
}
