<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120024422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customers CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE phone phone VARCHAR(30) DEFAULT NULL, CHANGE document_number document_number VARCHAR(30) DEFAULT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CUSTOMERS_EMAIL ON customers (email)');
        $this->addSql('ALTER TABLE location CHANGE name name VARCHAR(120) NOT NULL, CHANGE city city VARCHAR(120) NOT NULL, CHANGE address address VARCHAR(255) NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE reservation CHANGE status status VARCHAR(20) NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849559395C3F3 FOREIGN KEY (customer_id) REFERENCES customers (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955C77EA60D FOREIGN KEY (pickup_location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495511CB64C5 FOREIGN KEY (dropoff_location_id) REFERENCES location (id)');
        $this->addSql('CREATE INDEX IDX_42C849559395C3F3 ON reservation (customer_id)');
        $this->addSql('CREATE INDEX IDX_42C84955545317D1 ON reservation (vehicle_id)');
        $this->addSql('CREATE INDEX IDX_42C84955C77EA60D ON reservation (pickup_location_id)');
        $this->addSql('CREATE INDEX IDX_42C8495511CB64C5 ON reservation (dropoff_location_id)');
        $this->addSql('ALTER TABLE reservation_extra CHANGE name name VARCHAR(100) NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE reservation_extra ADD CONSTRAINT FK_E40DDC2B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_E40DDC2B83297E7 ON reservation_extra (reservation_id)');
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(180) NOT NULL, CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE password password VARCHAR(255) NOT NULL, CHANGE first_name first_name VARCHAR(100) NOT NULL, CHANGE last_name last_name VARCHAR(100) NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USERS_EMAIL ON users (email)');
        $this->addSql('ALTER TABLE vehicle CHANGE brand brand VARCHAR(80) NOT NULL, CHANGE model model VARCHAR(80) NOT NULL, CHANGE transmission transmission VARCHAR(20) NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E48612469DE2 FOREIGN KEY (category_id) REFERENCES vehicle_category (id)');
        $this->addSql('CREATE INDEX IDX_1B80E48612469DE2 ON vehicle (category_id)');
        $this->addSql('ALTER TABLE vehicle_category CHANGE name name VARCHAR(120) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE vehicle_location_stock ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE vehicle_location_stock ADD CONSTRAINT FK_468CE9B6545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vehicle_location_stock ADD CONSTRAINT FK_468CE9B664D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_468CE9B6545317D1 ON vehicle_location_stock (vehicle_id)');
        $this->addSql('CREATE INDEX IDX_468CE9B664D218E ON vehicle_location_stock (location_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_vehicle_location ON vehicle_location_stock (vehicle_id, location_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customers MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON customers');
        $this->addSql('DROP INDEX UNIQ_CUSTOMERS_EMAIL ON customers');
        $this->addSql('ALTER TABLE customers CHANGE first_name first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE last_name last_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE email email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE phone phone VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE document_number document_number VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE location MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON location');
        $this->addSql('ALTER TABLE location CHANGE name name VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE city city VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE address address VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE reservation MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849559395C3F3');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955545317D1');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955C77EA60D');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495511CB64C5');
        $this->addSql('DROP INDEX IDX_42C849559395C3F3 ON reservation');
        $this->addSql('DROP INDEX IDX_42C84955545317D1 ON reservation');
        $this->addSql('DROP INDEX IDX_42C84955C77EA60D ON reservation');
        $this->addSql('DROP INDEX IDX_42C8495511CB64C5 ON reservation');
        $this->addSql('DROP INDEX `primary` ON reservation');
        $this->addSql('ALTER TABLE reservation CHANGE status status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE reservation_extra MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation_extra DROP FOREIGN KEY FK_E40DDC2B83297E7');
        $this->addSql('DROP INDEX IDX_E40DDC2B83297E7 ON reservation_extra');
        $this->addSql('DROP INDEX `primary` ON reservation_extra');
        $this->addSql('ALTER TABLE reservation_extra CHANGE name name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE users MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON users');
        $this->addSql('DROP INDEX UNIQ_USERS_EMAIL ON users');
        $this->addSql('ALTER TABLE users CHANGE email email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\', CHANGE password password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE first_name first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE last_name last_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE vehicle MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E48612469DE2');
        $this->addSql('DROP INDEX IDX_1B80E48612469DE2 ON vehicle');
        $this->addSql('DROP INDEX `primary` ON vehicle');
        $this->addSql('ALTER TABLE vehicle CHANGE brand brand VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE model model VARCHAR(80) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE transmission transmission VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE vehicle_category MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON vehicle_category');
        $this->addSql('ALTER TABLE vehicle_category CHANGE name name VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE description description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE vehicle_location_stock MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE vehicle_location_stock DROP FOREIGN KEY FK_468CE9B6545317D1');
        $this->addSql('ALTER TABLE vehicle_location_stock DROP FOREIGN KEY FK_468CE9B664D218E');
        $this->addSql('DROP INDEX IDX_468CE9B6545317D1 ON vehicle_location_stock');
        $this->addSql('DROP INDEX IDX_468CE9B664D218E ON vehicle_location_stock');
        $this->addSql('DROP INDEX `primary` ON vehicle_location_stock');
        $this->addSql('DROP INDEX uniq_vehicle_location ON vehicle_location_stock');
    }
}
