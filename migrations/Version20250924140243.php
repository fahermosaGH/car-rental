<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250924140243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, vehicle_id INT NOT NULL, pickup_location_id INT NOT NULL, dropoff_location_id INT NOT NULL, start_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(20) NOT NULL, total_price NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_42C849559395C3F3 (customer_id), INDEX IDX_42C84955545317D1 (vehicle_id), INDEX IDX_42C84955C77EA60D (pickup_location_id), INDEX IDX_42C8495511CB64C5 (dropoff_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849559395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955C77EA60D FOREIGN KEY (pickup_location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495511CB64C5 FOREIGN KEY (dropoff_location_id) REFERENCES location (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849559395C3F3');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955545317D1');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955C77EA60D');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495511CB64C5');
        $this->addSql('DROP TABLE reservation');
    }
}
