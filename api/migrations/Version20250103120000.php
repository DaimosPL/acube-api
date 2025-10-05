<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for creating uploaded_files table.
 */
final class Version20250103120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create uploaded_files table for file upload management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE uploaded_files (
            id SERIAL PRIMARY KEY,
            path VARCHAR(500) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            extension VARCHAR(50) NOT NULL,
            size INTEGER NOT NULL,
            mime_type VARCHAR(32) NOT NULL,
            status VARCHAR(255) NOT NULL DEFAULT \'new\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');

        $this->addSql('COMMENT ON COLUMN uploaded_files.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN uploaded_files.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX idx_uploaded_files_status ON uploaded_files (status)');
        $this->addSql('CREATE INDEX idx_uploaded_files_created_at ON uploaded_files (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE uploaded_files');
    }
}
