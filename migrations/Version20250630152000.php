<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250630152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove teams_id_id column from service_integration';
    }

    public function up(Schema $schema): void
    {
        // Drop the foreign key constraint first
        $this->addSql('ALTER TABLE service_integration DROP FOREIGN KEY FK_31734B378B7A66FA');
        
        // Now drop the column
        $this->addSql('ALTER TABLE service_integration DROP teams_id_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_integration ADD teams_id_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_integration ADD CONSTRAINT FK_31734B378B7A66FA FOREIGN KEY (teams_id_id) REFERENCES teams_id (id)');
    }
}