<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251007141125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Índices para acelerar chequeo de disponibilidad: (vehicle_id,start_at) y (vehicle_id,end_at).';
    }

    public function up(Schema $schema): void
    {
        // Aceleran el chequeo de solapamientos por vehículo y rango de fechas
        $this->addSql('CREATE INDEX idx_res_vehicle_start ON reservation (vehicle_id, start_at)');
        $this->addSql('CREATE INDEX idx_res_vehicle_end   ON reservation (vehicle_id, end_at)');
    }

    public function down(Schema $schema): void
    {
        // Rollback de los índices
        $this->addSql('DROP INDEX idx_res_vehicle_start ON reservation');
        $this->addSql('DROP INDEX idx_res_vehicle_end ON reservation');
    }
}