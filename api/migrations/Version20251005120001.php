<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251005120001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_error column to uploaded_files table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE uploaded_files ADD last_error TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE uploaded_files DROP COLUMN last_error');
    }
}

