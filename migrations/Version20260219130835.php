<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219130835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation_incident (id INT AUTO_INCREMENT NOT NULL, reservation_id INT NOT NULL, vehicle_unit_id INT DEFAULT NULL, description LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A0B6430EB83297E7 (reservation_id), INDEX IDX_A0B6430EE7CE2D62 (vehicle_unit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation_incident ADD CONSTRAINT FK_A0B6430EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_incident ADD CONSTRAINT FK_A0B6430EE7CE2D62 FOREIGN KEY (vehicle_unit_id) REFERENCES vehicle_unit (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reservation ADD vehicle_unit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955E7CE2D62 FOREIGN KEY (vehicle_unit_id) REFERENCES vehicle_unit (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_42C84955E7CE2D62 ON reservation (vehicle_unit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_incident DROP FOREIGN KEY FK_A0B6430EB83297E7');
        $this->addSql('ALTER TABLE reservation_incident DROP FOREIGN KEY FK_A0B6430EE7CE2D62');
        $this->addSql('DROP TABLE reservation_incident');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955E7CE2D62');
        $this->addSql('DROP INDEX IDX_42C84955E7CE2D62 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP vehicle_unit_id');
    }
}
