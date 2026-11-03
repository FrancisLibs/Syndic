<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261014142706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resolution ADD budget_id INT DEFAULT NULL, ADD chantier_id INT DEFAULT NULL, ADD vote_complementaire_chantier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resolution ADD CONSTRAINT FK_FDD30F8A36ABA6B8 FOREIGN KEY (budget_id) REFERENCES budget (id)');
        $this->addSql('ALTER TABLE resolution ADD CONSTRAINT FK_FDD30F8AD0C0049D FOREIGN KEY (chantier_id) REFERENCES chantier (id)');
        $this->addSql('ALTER TABLE resolution ADD CONSTRAINT FK_FDD30F8A8A7237C9 FOREIGN KEY (vote_complementaire_chantier_id) REFERENCES vote_complementaire_chantier (id)');
        $this->addSql('CREATE INDEX IDX_FDD30F8A36ABA6B8 ON resolution (budget_id)');
        $this->addSql('CREATE INDEX IDX_FDD30F8AD0C0049D ON resolution (chantier_id)');
        $this->addSql('CREATE INDEX IDX_FDD30F8A8A7237C9 ON resolution (vote_complementaire_chantier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resolution DROP FOREIGN KEY FK_FDD30F8A36ABA6B8');
        $this->addSql('ALTER TABLE resolution DROP FOREIGN KEY FK_FDD30F8AD0C0049D');
        $this->addSql('ALTER TABLE resolution DROP FOREIGN KEY FK_FDD30F8A8A7237C9');
        $this->addSql('DROP INDEX IDX_FDD30F8A36ABA6B8 ON resolution');
        $this->addSql('DROP INDEX IDX_FDD30F8AD0C0049D ON resolution');
        $this->addSql('DROP INDEX IDX_FDD30F8A8A7237C9 ON resolution');
        $this->addSql('ALTER TABLE resolution DROP budget_id, DROP chantier_id, DROP vote_complementaire_chantier_id');
    }
}
