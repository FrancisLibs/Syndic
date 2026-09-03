<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260817070735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_reglement_fournisseur (id INT AUTO_INCREMENT NOT NULL, date_reglement DATE NOT NULL, montant NUMERIC(10, 2) NOT NULL, reference VARCHAR(100) DEFAULT NULL, statut VARCHAR(255) NOT NULL, erreur LONGTEXT DEFAULT NULL, exercice_id INT NOT NULL, fournisseur_id INT NOT NULL, reglement_cree_id INT DEFAULT NULL, INDEX IDX_31D119EA89D40298 (exercice_id), INDEX IDX_31D119EA670C757F (fournisseur_id), UNIQUE INDEX UNIQ_31D119EA1733CB58 (reglement_cree_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE import_reglement_fournisseur ADD CONSTRAINT FK_31D119EA89D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id)');
        $this->addSql('ALTER TABLE import_reglement_fournisseur ADD CONSTRAINT FK_31D119EA670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE import_reglement_fournisseur ADD CONSTRAINT FK_31D119EA1733CB58 FOREIGN KEY (reglement_cree_id) REFERENCES reglement_fournisseur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_reglement_fournisseur DROP FOREIGN KEY FK_31D119EA89D40298');
        $this->addSql('ALTER TABLE import_reglement_fournisseur DROP FOREIGN KEY FK_31D119EA670C757F');
        $this->addSql('ALTER TABLE import_reglement_fournisseur DROP FOREIGN KEY FK_31D119EA1733CB58');
        $this->addSql('DROP TABLE import_reglement_fournisseur');
    }
}
