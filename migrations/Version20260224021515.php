<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224021515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, occurred_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', actor_email VARCHAR(180) DEFAULT NULL, action VARCHAR(20) NOT NULL, entity_class VARCHAR(255) NOT NULL, entity_id VARCHAR(64) DEFAULT NULL, changes JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', meta JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX idx_audit_occurred_at (occurred_at), INDEX idx_audit_actor_email (actor_email), INDEX idx_audit_entity (entity_class, entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation_incident DROP FOREIGN KEY FK_res_inc_replacement_unit');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE audit_log');
    }
}
