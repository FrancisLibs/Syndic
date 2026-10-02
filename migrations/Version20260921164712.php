<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260921164712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mandat_copropriete (id INT AUTO_INCREMENT NOT NULL, fonction VARCHAR(50) NOT NULL, date_debut DATE NOT NULL, date_fin DATE DEFAULT NULL, copropriete_id INT NOT NULL, coproprietaire_id INT NOT NULL, INDEX IDX_FB252DE6B07769E (copropriete_id), INDEX IDX_FB252DEFF2D1A27 (coproprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE mandat_copropriete ADD CONSTRAINT FK_FB252DE6B07769E FOREIGN KEY (copropriete_id) REFERENCES copropriete (id)');
        $this->addSql('ALTER TABLE mandat_copropriete ADD CONSTRAINT FK_FB252DEFF2D1A27 FOREIGN KEY (coproprietaire_id) REFERENCES coproprietaire (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mandat_copropriete DROP FOREIGN KEY FK_FB252DE6B07769E');
        $this->addSql('ALTER TABLE mandat_copropriete DROP FOREIGN KEY FK_FB252DEFF2D1A27');
        $this->addSql('DROP TABLE mandat_copropriete');
    }
}
