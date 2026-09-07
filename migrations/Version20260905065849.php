<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260905065849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_fournisseur CHANGE numero numero VARCHAR(100) DEFAULT NULL, CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE import_facture_fournisseur CHANGE type type VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_fournisseur CHANGE numero numero VARCHAR(100) NOT NULL, CHANGE type type VARCHAR(255) DEFAULT \'facture\' NOT NULL');
        $this->addSql('ALTER TABLE import_facture_fournisseur CHANGE type type VARCHAR(255) DEFAULT \'facture\' NOT NULL');
    }
}
