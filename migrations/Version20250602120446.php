<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250602120446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create n8n_environment_variables table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE n8n_environment_variables (
            id INT AUTO_INCREMENT NOT NULL, 
            user_id INT NOT NULL, 
            variable_name VARCHAR(255) NOT NULL, 
            variable_value LONGTEXT DEFAULT NULL, 
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', 
            INDEX IDX_N8N_ENV_VARS_USER_ID (user_id), 
            UNIQUE INDEX unique_user_variable (user_id, variable_name), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE n8n_environment_variables ADD CONSTRAINT FK_N8N_ENV_VARS_USER_ID FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE n8n_environment_variables DROP FOREIGN KEY FK_N8N_ENV_VARS_USER_ID');
        $this->addSql('DROP TABLE n8n_environment_variables');
    }
}
