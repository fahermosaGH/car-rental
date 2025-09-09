<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250909184327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vehicle_location_stock (id INT AUTO_INCREMENT NOT NULL, vehicle_id INT NOT NULL, location_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_468CE9B6545317D1 (vehicle_id), INDEX IDX_468CE9B664D218E (location_id), UNIQUE INDEX uniq_vehicle_location (vehicle_id, location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE vehicle_location_stock ADD CONSTRAINT FK_468CE9B6545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicle_location_stock ADD CONSTRAINT FK_468CE9B664D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle_location_stock DROP FOREIGN KEY FK_468CE9B6545317D1');
        $this->addSql('ALTER TABLE vehicle_location_stock DROP FOREIGN KEY FK_468CE9B664D218E');
        $this->addSql('DROP TABLE vehicle_location_stock');
    }
}
