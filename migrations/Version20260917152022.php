<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260917152022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE presence_assemblee_generale (id INT AUTO_INCREMENT NOT NULL, nombre_voix INT NOT NULL, mandataire VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, assemblee_generale_id INT NOT NULL, coproprietaire_id INT NOT NULL, INDEX IDX_77FFFFD0BBE76FA9 (assemblee_generale_id), INDEX IDX_77FFFFD0FF2D1A27 (coproprietaire_id), UNIQUE INDEX UNIQ_PRESENCE_AG_COPROPRIETAIRE (assemblee_generale_id, coproprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE presence_assemblee_generale ADD CONSTRAINT FK_77FFFFD0BBE76FA9 FOREIGN KEY (assemblee_generale_id) REFERENCES assemblee_generale (id)');
        $this->addSql('ALTER TABLE presence_assemblee_generale ADD CONSTRAINT FK_77FFFFD0FF2D1A27 FOREIGN KEY (coproprietaire_id) REFERENCES coproprietaire (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE presence_assemblee_generale DROP FOREIGN KEY FK_77FFFFD0BBE76FA9');
        $this->addSql('ALTER TABLE presence_assemblee_generale DROP FOREIGN KEY FK_77FFFFD0FF2D1A27');
        $this->addSql('DROP TABLE presence_assemblee_generale');
    }
}
