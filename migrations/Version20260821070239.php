<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260821070239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur DROP FOREIGN KEY `FK_7FF91156A477111`');
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur DROP FOREIGN KEY `FK_7FF91157F2DEE08`');
        $this->addSql('DROP TABLE affectation_reglement_fournisseur');
        $this->addSql('ALTER TABLE facture_fournisseur DROP date_reglement, DROP montant_regle, DROP soldee');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE affectation_reglement_fournisseur (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(10, 2) NOT NULL, reglement_id INT NOT NULL, facture_id INT NOT NULL, INDEX IDX_7FF91156A477111 (reglement_id), INDEX IDX_7FF91157F2DEE08 (facture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur ADD CONSTRAINT `FK_7FF91156A477111` FOREIGN KEY (reglement_id) REFERENCES reglement_fournisseur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur ADD CONSTRAINT `FK_7FF91157F2DEE08` FOREIGN KEY (facture_id) REFERENCES facture_fournisseur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE facture_fournisseur ADD date_reglement DATE DEFAULT NULL, ADD montant_regle NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, ADD soldee TINYINT NOT NULL');
    }
}
