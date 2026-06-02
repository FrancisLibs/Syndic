<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260529080117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paiement ADD lot_coproprietaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E8388EF0D FOREIGN KEY (lot_coproprietaire_id) REFERENCES lot_coproprietaire (id)');
        $this->addSql('CREATE INDEX IDX_B1DC7A1E8388EF0D ON paiement (lot_coproprietaire_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E8388EF0D');
        $this->addSql('DROP INDEX IDX_B1DC7A1E8388EF0D ON paiement');
        $this->addSql('ALTER TABLE paiement DROP lot_coproprietaire_id');
    }
}
