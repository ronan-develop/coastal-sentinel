<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260811170650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modèle de données MVP : Zone, DataSource, EnvironmentReading, RiskThreshold, RiskAssessment.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE data_sources (id BINARY(16) NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, last_successful_ingestion_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_B65633035E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE environment_readings (id BINARY(16) NOT NULL, variable VARCHAR(255) NOT NULL, value DOUBLE PRECISION NOT NULL, unit VARCHAR(255) NOT NULL, measured_at DATETIME NOT NULL, horizon INT DEFAULT NULL, raw_payload JSON DEFAULT NULL, ingested_at DATETIME NOT NULL, zone_id BINARY(16) NOT NULL, data_source_id BINARY(16) NOT NULL, INDEX IDX_FE28CA4D9F2C3FAB (zone_id), INDEX IDX_FE28CA4D1A935C57 (data_source_id), UNIQUE INDEX uniq_reading (zone_id, data_source_id, variable, measured_at, horizon), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE risk_assessments (id BINARY(16) NOT NULL, risk_type VARCHAR(255) NOT NULL, score DOUBLE PRECISION NOT NULL, window_start DATE NOT NULL, window_end DATE NOT NULL, recommended_action VARCHAR(255) NOT NULL, computed_at DATETIME NOT NULL, zone_id BINARY(16) NOT NULL, INDEX IDX_9191E45B9F2C3FAB (zone_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE risk_thresholds (id BINARY(16) NOT NULL, risk_type VARCHAR(255) NOT NULL, variable VARCHAR(255) NOT NULL, operator VARCHAR(255) NOT NULL, value DOUBLE PRECISION NOT NULL, source VARCHAR(255) NOT NULL, min_exposure_days INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE zones (id BINARY(16) NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, geometry LONGTEXT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_85CAB16877153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE environment_readings ADD CONSTRAINT FK_FE28CA4D9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zones (id)');
        $this->addSql('ALTER TABLE environment_readings ADD CONSTRAINT FK_FE28CA4D1A935C57 FOREIGN KEY (data_source_id) REFERENCES data_sources (id)');
        $this->addSql('ALTER TABLE risk_assessments ADD CONSTRAINT FK_9191E45B9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zones (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE environment_readings DROP FOREIGN KEY FK_FE28CA4D9F2C3FAB');
        $this->addSql('ALTER TABLE environment_readings DROP FOREIGN KEY FK_FE28CA4D1A935C57');
        $this->addSql('ALTER TABLE risk_assessments DROP FOREIGN KEY FK_9191E45B9F2C3FAB');
        $this->addSql('DROP TABLE data_sources');
        $this->addSql('DROP TABLE environment_readings');
        $this->addSql('DROP TABLE risk_assessments');
        $this->addSql('DROP TABLE risk_thresholds');
        $this->addSql('DROP TABLE zones');
    }
}
