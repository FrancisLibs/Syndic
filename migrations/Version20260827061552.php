<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260827061552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE approbation_comptes ADD exercice_comptabilisation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE approbation_comptes ADD CONSTRAINT FK_C49DA083D688CDCA FOREIGN KEY (exercice_comptabilisation_id) REFERENCES exercice (id)');
        $this->addSql('CREATE INDEX IDX_C49DA083D688CDCA ON approbation_comptes (exercice_comptabilisation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE approbation_comptes DROP FOREIGN KEY FK_C49DA083D688CDCA');
        $this->addSql('DROP INDEX IDX_C49DA083D688CDCA ON approbation_comptes');
        $this->addSql('ALTER TABLE approbation_comptes DROP exercice_comptabilisation_id');
    }
}
