<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812113738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE environment_reading_cells (id BINARY(16) NOT NULL, variable VARCHAR(255) NOT NULL, lat DOUBLE PRECISION NOT NULL, lon DOUBLE PRECISION NOT NULL, value DOUBLE PRECISION DEFAULT NULL, measured_at DATETIME NOT NULL, ingested_at DATETIME NOT NULL, zone_id BINARY(16) NOT NULL, data_source_id BINARY(16) NOT NULL, INDEX IDX_205D47649F2C3FAB (zone_id), INDEX IDX_205D47641A935C57 (data_source_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE environment_reading_cells ADD CONSTRAINT FK_205D47649F2C3FAB FOREIGN KEY (zone_id) REFERENCES zones (id)');
        $this->addSql('ALTER TABLE environment_reading_cells ADD CONSTRAINT FK_205D47641A935C57 FOREIGN KEY (data_source_id) REFERENCES data_sources (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE environment_reading_cells DROP FOREIGN KEY FK_205D47649F2C3FAB');
        $this->addSql('ALTER TABLE environment_reading_cells DROP FOREIGN KEY FK_205D47641A935C57');
        $this->addSql('DROP TABLE environment_reading_cells');
    }
}
