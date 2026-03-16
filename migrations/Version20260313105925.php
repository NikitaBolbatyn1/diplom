<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313105925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE archive_file (id INT AUTO_INCREMENT NOT NULL, original_filename VARCHAR(255) NOT NULL, stored_filename VARCHAR(255) NOT NULL, file_path VARCHAR(255) NOT NULL, downloaded_at DATETIME NOT NULL, created_at DATETIME NOT NULL, file_size INT NOT NULL, file_type VARCHAR(50) NOT NULL, downloaded_by_id INT NOT NULL, INDEX IDX_BCBAE08BCBFB94C3 (downloaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE faculty (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, head_name VARCHAR(255) NOT NULL, head_degree VARCHAR(255) DEFAULT NULL, head_position VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE archive_file ADD CONSTRAINT FK_BCBAE08BCBFB94C3 FOREIGN KEY (downloaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event ADD faculty_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7680CAB68 FOREIGN KEY (faculty_id) REFERENCES faculty (id)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7680CAB68 ON event (faculty_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE archive_file DROP FOREIGN KEY FK_BCBAE08BCBFB94C3');
        $this->addSql('DROP TABLE archive_file');
        $this->addSql('DROP TABLE faculty');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7680CAB68');
        $this->addSql('DROP INDEX IDX_3BAE0AA7680CAB68 ON event');
        $this->addSql('ALTER TABLE event DROP faculty_id');
    }
}
