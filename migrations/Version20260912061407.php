<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260912061407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_fournisseur CHANGE fnp fnp TINYINT NOT NULL, CHANGE fnp_traitee fnp_traitee TINYINT NOT NULL');
        $this->addSql('ALTER TABLE ligne_appel_fond ADD solde_avant_appel NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_fournisseur CHANGE fnp fnp TINYINT DEFAULT 0 NOT NULL, CHANGE fnp_traitee fnp_traitee TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ligne_appel_fond DROP solde_avant_appel');
    }
}
