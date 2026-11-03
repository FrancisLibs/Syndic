<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261024152256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chantier CHANGE financement financement VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE copropriete ADD iban VARCHAR(34) DEFAULT NULL, ADD bic VARCHAR(11) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chantier CHANGE financement financement VARCHAR(255) DEFAULT \'appels_specifiques\' NOT NULL');
        $this->addSql('ALTER TABLE copropriete DROP iban, DROP bic');
    }
}
