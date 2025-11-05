<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105003326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_reservation_vehicle_start ON reservation');
        $this->addSql('DROP INDEX idx_reservation_pickup ON reservation');
        $this->addSql('DROP INDEX idx_reservation_vehicle_end ON reservation');
        $this->addSql('DROP INDEX idx_vls_location_vehicle ON vehicle_location_stock');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_reservation_vehicle_start ON reservation (vehicle_id, start_at)');
        $this->addSql('CREATE INDEX idx_reservation_pickup ON reservation (pickup_location_id)');
        $this->addSql('CREATE INDEX idx_reservation_vehicle_end ON reservation (vehicle_id, end_at)');
        $this->addSql('CREATE INDEX idx_vls_location_vehicle ON vehicle_location_stock (location_id, vehicle_id)');
    }
}
