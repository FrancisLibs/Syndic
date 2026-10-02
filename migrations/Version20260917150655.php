<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260917150655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vote_resolution (id INT AUTO_INCREMENT NOT NULL, nombre_voix INT NOT NULL, choix VARCHAR(255) NOT NULL, resolution_id INT NOT NULL, coproprietaire_id INT NOT NULL, INDEX IDX_169F757212A1C43A (resolution_id), INDEX IDX_169F7572FF2D1A27 (coproprietaire_id), UNIQUE INDEX UNIQ_VOTE_RESOLUTION_COPROPRIETAIRE (resolution_id, coproprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE vote_resolution ADD CONSTRAINT FK_169F757212A1C43A FOREIGN KEY (resolution_id) REFERENCES resolution (id)');
        $this->addSql('ALTER TABLE vote_resolution ADD CONSTRAINT FK_169F7572FF2D1A27 FOREIGN KEY (coproprietaire_id) REFERENCES coproprietaire (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vote_resolution DROP FOREIGN KEY FK_169F757212A1C43A');
        $this->addSql('ALTER TABLE vote_resolution DROP FOREIGN KEY FK_169F7572FF2D1A27');
        $this->addSql('DROP TABLE vote_resolution');
    }
}
