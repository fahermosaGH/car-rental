<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223143826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_incident CHANGE resolved_at resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reservation_incident ADD CONSTRAINT FK_A0B6430E2BF9B508 FOREIGN KEY (replacement_unit_id) REFERENCES vehicle_unit (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A0B6430E2BF9B508 ON reservation_incident (replacement_unit_id)');
        $this->addSql('ALTER TABLE vehicle CHANGE image_url image_url VARCHAR(1024) DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle_category ADD is_active TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_incident DROP FOREIGN KEY FK_A0B6430E2BF9B508');
        $this->addSql('DROP INDEX IDX_A0B6430E2BF9B508 ON reservation_incident');
        $this->addSql('ALTER TABLE reservation_incident CHANGE resolved_at resolved_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle CHANGE image_url image_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle_category DROP is_active');
    }
}
