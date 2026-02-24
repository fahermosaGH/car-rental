<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221130107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE reservation_incident ADD replacement_unit_id INT DEFAULT NULL, ADD resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        //$this->addSql('ALTER TABLE reservation_incident ADD CONSTRAINT FK_A0B6430E2BF9B508 FOREIGN KEY (replacement_unit_id) REFERENCES vehicle_unit (id) ON DELETE SET NULL');
        //$this->addSql('CREATE INDEX IDX_A0B6430E2BF9B508 ON reservation_incident (replacement_unit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE reservation_incident DROP FOREIGN KEY FK_A0B6430E2BF9B508');
        //$this->addSql('DROP INDEX IDX_A0B6430E2BF9B508 ON reservation_incident');
        //$this->addSql('ALTER TABLE reservation_incident DROP replacement_unit_id, DROP resolved_at');
    }
}
