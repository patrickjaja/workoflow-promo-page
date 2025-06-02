<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602083833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token DROP INDEX UNIQ_B6A2DD68A76ED395, ADD INDEX IDX_B6A2DD68A76ED395 (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_USER_SERVICE ON access_token (user_id, service)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE access_token DROP INDEX IDX_B6A2DD68A76ED395, ADD UNIQUE INDEX UNIQ_B6A2DD68A76ED395 (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_USER_SERVICE ON access_token
        SQL);
    }
}
