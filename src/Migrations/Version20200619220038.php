<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200619220038 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, province_id INT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, INDEX IDX_2D5B0234E946114A (province_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL, is_default TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE members (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, email VARCHAR(100) NOT NULL, mobile_number VARCHAR(100) DEFAULT NULL, enabled TINYINT(1) NOT NULL, birth_day DATETIME DEFAULT NULL, create_date DATETIME NOT NULL, username VARCHAR(100) DEFAULT NULL, password VARCHAR(255) NOT NULL, genre VARCHAR(20) DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, wish_number INT DEFAULT NULL, wish_list VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_45A0D2FFE7927C74 (email), UNIQUE INDEX UNIQ_45A0D2FFF85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE province (id INT AUTO_INCREMENT NOT NULL, region_id INT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, INDEX IDX_4ADAD40B98260155 (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quartier (id INT AUTO_INCREMENT NOT NULL, city_id INT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, INDEX IDX_FEE8962D8BAC62AF (city_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, country_id INT NOT NULL, code VARCHAR(50) NOT NULL, name VARCHAR(50) NOT NULL, enabled TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, INDEX IDX_F62F176F92F3E70 (country_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', password VARCHAR(255) NOT NULL, first_name VARCHAR(45) DEFAULT NULL, last_name VARCHAR(45) DEFAULT NULL, mobile_number VARCHAR(45) DEFAULT NULL, create_date DATETIME NOT NULL, enabled TINYINT(1) NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE city ADD CONSTRAINT FK_2D5B0234E946114A FOREIGN KEY (province_id) REFERENCES province (id)');
        $this->addSql('ALTER TABLE province ADD CONSTRAINT FK_4ADAD40B98260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE quartier ADD CONSTRAINT FK_FEE8962D8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE region ADD CONSTRAINT FK_F62F176F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE quartier DROP FOREIGN KEY FK_FEE8962D8BAC62AF');
        $this->addSql('ALTER TABLE region DROP FOREIGN KEY FK_F62F176F92F3E70');
        $this->addSql('ALTER TABLE city DROP FOREIGN KEY FK_2D5B0234E946114A');
        $this->addSql('ALTER TABLE province DROP FOREIGN KEY FK_4ADAD40B98260155');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE members');
        $this->addSql('DROP TABLE province');
        $this->addSql('DROP TABLE quartier');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE user');
    }
}
