<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301010636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO user (email, roles, password, full_name, department, is_active, created_at) VALUES ('admin', '[\"ROLE_ADMIN\"]', '$2y$13$4hfLqshdJMUQkx/.KPxE7OMeZg8pe8LDGqSUX5bGtCO6aW55A2Sxm', 'Администратор', NULL, 1, NOW())");
    }

    public function down(Schema $schema): void
    {

    }
}
