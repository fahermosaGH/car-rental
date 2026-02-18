<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215210509 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vehicle_unit (id INT AUTO_INCREMENT NOT NULL, vehicle_id INT NOT NULL, location_id INT NOT NULL, plate VARCHAR(16) NOT NULL, status VARCHAR(20) NOT NULL, INDEX IDX_AE263F0F545317D1 (vehicle_id), INDEX IDX_AE263F0F64D218E (location_id), UNIQUE INDEX uniq_vehicle_unit_plate (plate), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vehicle_unit ADD CONSTRAINT FK_AE263F0F545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicle_unit ADD CONSTRAINT FK_AE263F0F64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle_unit DROP FOREIGN KEY FK_AE263F0F545317D1');
        $this->addSql('ALTER TABLE vehicle_unit DROP FOREIGN KEY FK_AE263F0F64D218E');
        $this->addSql('DROP TABLE vehicle_unit');
    }
}
