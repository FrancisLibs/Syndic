<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261108194829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rapprochement_bancaire (id INT AUTO_INCREMENT NOT NULL, date_rapprochement DATE NOT NULL, reference_releve VARCHAR(100) DEFAULT NULL, ecriture_id INT NOT NULL, UNIQUE INDEX UNIQ_RAPPROCHEMENT_ECRITURE (ecriture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE rapprochement_bancaire ADD CONSTRAINT FK_D4D082D03407A4D0 FOREIGN KEY (ecriture_id) REFERENCES ecriture (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rapprochement_bancaire DROP FOREIGN KEY FK_D4D082D03407A4D0');
        $this->addSql('DROP TABLE rapprochement_bancaire');
    }
}
