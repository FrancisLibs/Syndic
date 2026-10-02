<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915060439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE type_charge ADD type_charge_budget_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE type_charge ADD CONSTRAINT FK_76BCCF0C8940D4B FOREIGN KEY (type_charge_budget_id) REFERENCES type_charge (id)');
        $this->addSql('CREATE INDEX IDX_76BCCF0C8940D4B ON type_charge (type_charge_budget_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE type_charge DROP FOREIGN KEY FK_76BCCF0C8940D4B');
        $this->addSql('DROP INDEX IDX_76BCCF0C8940D4B ON type_charge');
        $this->addSql('ALTER TABLE type_charge DROP type_charge_budget_id');
    }
}
