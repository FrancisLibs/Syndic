<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260716160441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE approbation_comptes (id INT AUTO_INCREMENT NOT NULL, date_assemblee_generale DATETIME NOT NULL, numero_resolution VARCHAR(50) DEFAULT NULL, validee TINYINT DEFAULT 0 NOT NULL, exercice_id INT NOT NULL, operation_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_C49DA08389D40298 (exercice_id), UNIQUE INDEX UNIQ_C49DA08344AC3583 (operation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE approbation_comptes ADD CONSTRAINT FK_C49DA08389D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE approbation_comptes ADD CONSTRAINT FK_C49DA08344AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE repartition ADD CONSTRAINT FK_82B791A089D40298 FOREIGN KEY (exercice_id) REFERENCES exercice (id)');
        $this->addSql('CREATE INDEX IDX_82B791A089D40298 ON repartition (exercice_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE approbation_comptes DROP FOREIGN KEY FK_C49DA08389D40298');
        $this->addSql('ALTER TABLE approbation_comptes DROP FOREIGN KEY FK_C49DA08344AC3583');
        $this->addSql('DROP TABLE approbation_comptes');
        $this->addSql('ALTER TABLE repartition DROP FOREIGN KEY FK_82B791A089D40298');
        $this->addSql('DROP INDEX IDX_82B791A089D40298 ON repartition');
    }
}
