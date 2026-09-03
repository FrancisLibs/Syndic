<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802155556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE import_ecriture (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, libelle VARCHAR(255) NOT NULL, montant NUMERIC(10, 2) NOT NULL, etat VARCHAR(255) NOT NULL, message_erreur LONGTEXT DEFAULT NULL, date_import DATETIME DEFAULT NULL, compte_adebiter_id INT NOT NULL, compte_acrediter_id INT NOT NULL, exercice_id INT NOT NULL, operation_id INT DEFAULT NULL, INDEX IDX_AAA63311D837F8E (compte_adebiter_id), INDEX IDX_AAA633115AAE67B0 (compte_acrediter_id), INDEX IDX_AAA6331189D40298 (exercice_id), UNIQUE INDEX UNIQ_AAA6331144AC3583 (operation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE import_ecriture ADD CONSTRAINT FK_AAA63311D837F8E FOREIGN KEY (compte_adebiter_id) REFERENCES compte (id)');
        $this->addSql('ALTER TABLE import_ecriture ADD CONSTRAINT FK_AAA633115AAE67B0 FOREIGN KEY (compte_acrediter_id) REFERENCES compte (id)');
        $this->addSql('ALTER TABLE import_ecriture ADD CONSTRAINT FK_AAA6331189D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id)');
        $this->addSql('ALTER TABLE import_ecriture ADD CONSTRAINT FK_AAA6331144AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_ecriture DROP FOREIGN KEY FK_AAA63311D837F8E');
        $this->addSql('ALTER TABLE import_ecriture DROP FOREIGN KEY FK_AAA633115AAE67B0');
        $this->addSql('ALTER TABLE import_ecriture DROP FOREIGN KEY FK_AAA6331189D40298');
        $this->addSql('ALTER TABLE import_ecriture DROP FOREIGN KEY FK_AAA6331144AC3583');
        $this->addSql('DROP TABLE import_ecriture');
    }
}
