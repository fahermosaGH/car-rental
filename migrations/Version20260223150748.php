<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223150748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing FK for reservation_incident.replacement_unit_id and normalize vehicle.image_url. Index already exists; category is_active already exists.';
    }

    public function up(Schema $schema): void
    {
        // Keep this if your column is datetime_immutable in Doctrine
        $this->addSql(
            "ALTER TABLE reservation_incident CHANGE resolved_at resolved_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        // FK is missing (confirmed). Create ONLY the FK.
        $this->addSql(
            'ALTER TABLE reservation_incident ADD CONSTRAINT FK_res_inc_replacement_unit FOREIGN KEY (replacement_unit_id) REFERENCES vehicle_unit (id) ON DELETE SET NULL'
        );

        // Do NOT create index: you already have TWO indexes for replacement_unit_id
        // $this->addSql('CREATE INDEX ...');

        // Normalize image_url length (safe)
        $this->addSql('ALTER TABLE vehicle CHANGE image_url image_url VARCHAR(1024) DEFAULT NULL');

        // vehicle_category.is_active already exists -> do nothing
    }

    public function down(Schema $schema): void
    {
        // Drop FK we added
        $this->addSql('ALTER TABLE reservation_incident DROP FOREIGN KEY FK_res_inc_replacement_unit');

        // Keep down compatible (don’t touch indexes that pre-existed)
        $this->addSql('ALTER TABLE reservation_incident CHANGE resolved_at resolved_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE vehicle CHANGE image_url image_url VARCHAR(500) DEFAULT NULL');
    }
}