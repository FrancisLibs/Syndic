<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260908140212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compteur_eau DROP INDEX UNIQ_9B1D4CE3A8CBA5F7, ADD INDEX IDX_9B1D4CE3A8CBA5F7 (lot_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compteur_eau DROP INDEX IDX_9B1D4CE3A8CBA5F7, ADD UNIQUE INDEX UNIQ_9B1D4CE3A8CBA5F7 (lot_id)');
    }
}
