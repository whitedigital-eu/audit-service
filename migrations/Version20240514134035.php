<?php

declare(strict_types=1);

namespace Whitedigital\Audit\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240514134035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Alter any existing audit table to use whitedigital schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS whitedigital');
        $this->addSql('ALTER TABLE IF EXISTS audit SET SCHEMA whitedigital');
        $this->addSql('ALTER INDEX IF EXISTS whitedigital.idx_9218ff7964c19c1 RENAME TO IDX_1E4A63C64C19C1');
        $this->addSql('ALTER INDEX IF EXISTS whitedigital.idx_9218ff79b6bd307f RENAME TO IDX_1E4A63CB6BD307F');
        $this->addSql('ALTER INDEX IF EXISTS whitedigital.idx_9218ff7922ffd58c RENAME TO IDX_1E4A63C22FFD58C');
        $this->addSql('ALTER INDEX IF EXISTS whitedigital.idx_9218ff79d0494586 RENAME TO IDX_1E4A63CD0494586');
        $this->addSql('ALTER INDEX IF EXISTS whitedigital.idx_9218ff798b8e8428 RENAME TO IDX_1E4A63C8B8E8428');
        $this->addSql('ALTER INDEX IF EXISTS whitedigital.idx_9218ff7943625d9f RENAME TO IDX_1E4A63C43625D9F');
    }

    public function down(Schema $schema): void
    {
    }
}
