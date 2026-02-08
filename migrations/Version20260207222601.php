<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207222601 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location CHANGE city city VARCHAR(120) DEFAULT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD is_active TINYINT(1) NOT NULL, CHANGE profile_complete profile_complete TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location CHANGE city city VARCHAR(120) NOT NULL, CHANGE latitude latitude NUMERIC(10, 7) DEFAULT NULL, CHANGE longitude longitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql('ALTER TABLE users DROP is_active, CHANGE profile_complete profile_complete TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
