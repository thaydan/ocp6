<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211111190750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE group_trick (group_id INT NOT NULL, trick_id INT NOT NULL, INDEX IDX_88DC8279FE54D947 (group_id), INDEX IDX_88DC8279B281BE2E (trick_id), PRIMARY KEY(group_id, trick_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE group_trick ADD CONSTRAINT FK_88DC8279FE54D947 FOREIGN KEY (group_id) REFERENCES `group` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_trick ADD CONSTRAINT FK_88DC8279B281BE2E FOREIGN KEY (trick_id) REFERENCES trick (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trick CHANGE featured_image_id featured_image_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_trick DROP FOREIGN KEY FK_88DC8279FE54D947');
        $this->addSql('DROP TABLE `group`');
        $this->addSql('DROP TABLE group_trick');
        $this->addSql('ALTER TABLE trick CHANGE featured_image_id featured_image_id INT DEFAULT NULL');
    }
}
