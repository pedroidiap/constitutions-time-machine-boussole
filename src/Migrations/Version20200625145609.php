<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200625145609 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE debat_intervenant (debat_id INT NOT NULL, intervenant_id INT NOT NULL, INDEX IDX_5D853D1F743EC92F (debat_id), INDEX IDX_5D853D1FAB9A1716 (intervenant_id), PRIMARY KEY(debat_id, intervenant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE debat_intervenant ADD CONSTRAINT FK_5D853D1F743EC92F FOREIGN KEY (debat_id) REFERENCES debat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE debat_intervenant ADD CONSTRAINT FK_5D853D1FAB9A1716 FOREIGN KEY (intervenant_id) REFERENCES intervenant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE debat DROP FOREIGN KEY FK_C6B5D12CAB9A1716');
        $this->addSql('DROP INDEX IDX_C6B5D12CAB9A1716 ON debat');
        $this->addSql('ALTER TABLE debat DROP intervenant_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE debat_intervenant');
        $this->addSql('ALTER TABLE debat ADD intervenant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE debat ADD CONSTRAINT FK_C6B5D12CAB9A1716 FOREIGN KEY (intervenant_id) REFERENCES intervenant (id)');
        $this->addSql('CREATE INDEX IDX_C6B5D12CAB9A1716 ON debat (intervenant_id)');
    }
}
