<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251005120002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification_status column to uploaded_files table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // make column nullable with no default
        $this->addSql("ALTER TABLE uploaded_files ADD notification_status VARCHAR(32) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE uploaded_files DROP COLUMN notification_status');
    }
}
