<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241214135259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `BasketProduct` (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, basket_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_90E030BE4584665A (product_id), INDEX IDX_90E030BE1BE1FB52 (basket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE basket (id INT AUTO_INCREMENT NOT NULL, product_name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, basket_id INT DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_F52993981BE1FB52 (basket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `BasketProduct` ADD CONSTRAINT FK_90E030BE4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE `BasketProduct` ADD CONSTRAINT FK_90E030BE1BE1FB52 FOREIGN KEY (basket_id) REFERENCES basket (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993981BE1FB52 FOREIGN KEY (basket_id) REFERENCES basket (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `BasketProduct` DROP FOREIGN KEY FK_90E030BE4584665A');
        $this->addSql('ALTER TABLE `BasketProduct` DROP FOREIGN KEY FK_90E030BE1BE1FB52');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993981BE1FB52');
        $this->addSql('DROP TABLE `BasketProduct`');
        $this->addSql('DROP TABLE basket');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE product');
    }
}
