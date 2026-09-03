<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807061614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE affectation_reglement_fournisseur (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(10, 2) NOT NULL, reglement_id INT NOT NULL, facture_id INT NOT NULL, INDEX IDX_7FF91156A477111 (reglement_id), INDEX IDX_7FF91157F2DEE08 (facture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reglement_fournisseur (id INT AUTO_INCREMENT NOT NULL, date_reglement DATE NOT NULL, montant NUMERIC(10, 2) NOT NULL, reference VARCHAR(100) DEFAULT NULL, fournisseur_id INT NOT NULL, exercice_id INT NOT NULL, operation_id INT DEFAULT NULL, INDEX IDX_BB07C330670C757F (fournisseur_id), INDEX IDX_BB07C33089D40298 (exercice_id), UNIQUE INDEX UNIQ_BB07C33044AC3583 (operation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur ADD CONSTRAINT FK_7FF91156A477111 FOREIGN KEY (reglement_id) REFERENCES reglement_fournisseur (id)');
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur ADD CONSTRAINT FK_7FF91157F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture_fournisseur (id)');
        $this->addSql('ALTER TABLE reglement_fournisseur ADD CONSTRAINT FK_BB07C330670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('ALTER TABLE reglement_fournisseur ADD CONSTRAINT FK_BB07C33089D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id)');
        $this->addSql('ALTER TABLE reglement_fournisseur ADD CONSTRAINT FK_BB07C33044AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur DROP FOREIGN KEY FK_7FF91156A477111');
        $this->addSql('ALTER TABLE affectation_reglement_fournisseur DROP FOREIGN KEY FK_7FF91157F2DEE08');
        $this->addSql('ALTER TABLE reglement_fournisseur DROP FOREIGN KEY FK_BB07C330670C757F');
        $this->addSql('ALTER TABLE reglement_fournisseur DROP FOREIGN KEY FK_BB07C33089D40298');
        $this->addSql('ALTER TABLE reglement_fournisseur DROP FOREIGN KEY FK_BB07C33044AC3583');
        $this->addSql('DROP TABLE affectation_reglement_fournisseur');
        $this->addSql('DROP TABLE reglement_fournisseur');
    }
}
