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
        return 'Fix: avoid duplicate FK/index on reservation_incident; keep other schema updates.';
    }

    public function up(Schema $schema): void
    {
        // 1) reservation_incident.resolved_at (safe/idempotent)
        $this->addSql(
            "ALTER TABLE reservation_incident CHANGE resolved_at resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        // 2) OJO: NO volver a crear FK_A0B6430E2BF9B508 ni IDX_A0B6430E2BF9B508
        // porque ya existen en tu DB y eso rompe con errno 121.

        // 3) vehicle.image_url length
        $this->addSql("ALTER TABLE vehicle CHANGE image_url image_url VARCHAR(1024) DEFAULT NULL");

        // 4) vehicle_category.is_active
        $this->addSql("ALTER TABLE vehicle_category ADD is_active TINYINT(1) NOT NULL");
    }

    public function down(Schema $schema): void
    {
        // DOWN conservador: revertimos solo lo que agregamos/cambiamos acá.
        // NO tocamos la FK/índice porque no los creamos en este up().

        $this->addSql("ALTER TABLE reservation_incident CHANGE resolved_at resolved_at DATETIME DEFAULT NULL");
        $this->addSql("ALTER TABLE vehicle CHANGE image_url image_url VARCHAR(500) DEFAULT NULL");
        $this->addSql("ALTER TABLE vehicle_category DROP is_active");
    }
}