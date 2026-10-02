<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260913063247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE resolution (id INT AUTO_INCREMENT NOT NULL, numero INT NOT NULL, titre VARCHAR(255) NOT NULL, texte LONGTEXT NOT NULL, majorite VARCHAR(50) DEFAULT NULL, assemblee_generale_id INT NOT NULL, INDEX IDX_FDD30F8ABBE76FA9 (assemblee_generale_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE resolution ADD CONSTRAINT FK_FDD30F8ABBE76FA9 FOREIGN KEY (assemblee_generale_id) REFERENCES assemblee_generale (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resolution DROP FOREIGN KEY FK_FDD30F8ABBE76FA9');
        $this->addSql('DROP TABLE resolution');
    }
}
