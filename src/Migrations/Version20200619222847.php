<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200619222847 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, create_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE products (id INT AUTO_INCREMENT NOT NULL, shop_id INT DEFAULT NULL, reference VARCHAR(100) NOT NULL, name VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, images VARCHAR(255) DEFAULT NULL, price DOUBLE PRECISION DEFAULT NULL, size LONGTEXT DEFAULT NULL, color LONGTEXT DEFAULT NULL, INDEX IDX_B3BA5A5A4D16C4DD (shop_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE shop (id INT AUTO_INCREMENT NOT NULL, region_id INT DEFAULT NULL, province_id INT DEFAULT NULL, city_id INT DEFAULT NULL, quartier_id INT DEFAULT NULL, member_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, longitude NUMERIC(10, 0) DEFAULT NULL, latitude NUMERIC(10, 0) DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, validated TINYINT(1) DEFAULT NULL, INDEX IDX_AC6A4CA298260155 (region_id), INDEX IDX_AC6A4CA2E946114A (province_id), INDEX IDX_AC6A4CA28BAC62AF (city_id), INDEX IDX_AC6A4CA2DF1E57AB (quartier_id), INDEX IDX_AC6A4CA27597D3FE (member_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sub_category (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) DEFAULT NULL, create_date DATETIME NOT NULL, INDEX IDX_BCE3F79812469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A4D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA298260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA2E946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA28BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA2DF1E57AB FOREIGN KEY (quartier_id) REFERENCES quartier (id)');
        $this->addSql('ALTER TABLE shop ADD CONSTRAINT FK_AC6A4CA27597D3FE FOREIGN KEY (member_id) REFERENCES members (id)');
        $this->addSql('ALTER TABLE sub_category ADD CONSTRAINT FK_BCE3F79812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sub_category DROP FOREIGN KEY FK_BCE3F79812469DE2');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A4D16C4DD');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE shop');
        $this->addSql('DROP TABLE sub_category');
    }
}
