<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918073500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_VOTE_RESOLUTION_COPROPRIETAIRE ON vote_resolution');
        $this->addSql('ALTER TABLE vote_resolution ADD tour INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_VOTE_RESOLUTION_COPROPRIETAIRE_TOUR ON vote_resolution (resolution_id, coproprietaire_id, tour)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_VOTE_RESOLUTION_COPROPRIETAIRE_TOUR ON vote_resolution');
        $this->addSql('ALTER TABLE vote_resolution DROP tour');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_VOTE_RESOLUTION_COPROPRIETAIRE ON vote_resolution (resolution_id, coproprietaire_id)');
    }
}
