<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001183754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chantier (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, montant_vote NUMERIC(10, 2) NOT NULL, date_vote DATETIME NOT NULL, numero_resolution VARCHAR(100) DEFAULT NULL, mode_repartition VARCHAR(255) NOT NULL, cloture TINYINT NOT NULL, copropriete_id INT NOT NULL, INDEX IDX_636F27F66B07769E (copropriete_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE chantier ADD CONSTRAINT FK_636F27F66B07769E FOREIGN KEY (copropriete_id) REFERENCES copropriete (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chantier DROP FOREIGN KEY FK_636F27F66B07769E');
        $this->addSql('DROP TABLE chantier');
    }
}
