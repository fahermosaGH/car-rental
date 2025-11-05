<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251021142957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_res_vehicle_start ON reservation');
        $this->addSql('DROP INDEX idx_res_vehicle_end ON reservation');
        $this->addSql('ALTER TABLE reservation CHANGE customer_id customer_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation CHANGE customer_id customer_id INT NOT NULL');
        $this->addSql('CREATE INDEX idx_res_vehicle_start ON reservation (vehicle_id, start_at)');
        $this->addSql('CREATE INDEX idx_res_vehicle_end ON reservation (vehicle_id, end_at)');
    }
}
