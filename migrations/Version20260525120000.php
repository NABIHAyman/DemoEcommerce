<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product_stats table for popularity tracking (Mission 2)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_stats (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, view_count INT DEFAULT 0 NOT NULL, add_to_cart_count INT DEFAULT 0 NOT NULL, purchase_count INT DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_719631F4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_stats ADD CONSTRAINT FK_719631F4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_stats DROP FOREIGN KEY FK_719631F4584665A');
        $this->addSql('DROP TABLE product_stats');
    }
}
