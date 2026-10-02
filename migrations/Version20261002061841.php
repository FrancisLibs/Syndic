<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002061841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appel_fond ADD chantier_id INT DEFAULT NULL, CHANGE budget_id budget_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appel_fond ADD CONSTRAINT FK_71C066AED0C0049D FOREIGN KEY (chantier_id) REFERENCES chantier (id)');
        $this->addSql('CREATE INDEX IDX_71C066AED0C0049D ON appel_fond (chantier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appel_fond DROP FOREIGN KEY FK_71C066AED0C0049D');
        $this->addSql('DROP INDEX IDX_71C066AED0C0049D ON appel_fond');
        $this->addSql('ALTER TABLE appel_fond DROP chantier_id, CHANGE budget_id budget_id INT NOT NULL');
    }
}
