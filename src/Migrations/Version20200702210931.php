<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200702210931 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE article_debat');
        $this->addSql('ALTER TABLE debat ADD article_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE debat ADD CONSTRAINT FK_C6B5D12C7294869C FOREIGN KEY (article_id) REFERENCES article (id)');
        $this->addSql('CREATE INDEX IDX_C6B5D12C7294869C ON debat (article_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE article_debat (article_id INT NOT NULL, debat_id INT NOT NULL, INDEX IDX_B102A33D7294869C (article_id), INDEX IDX_B102A33D743EC92F (debat_id), PRIMARY KEY(article_id, debat_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE article_debat ADD CONSTRAINT FK_B102A33D7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_debat ADD CONSTRAINT FK_B102A33D743EC92F FOREIGN KEY (debat_id) REFERENCES debat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE debat DROP FOREIGN KEY FK_C6B5D12C7294869C');
        $this->addSql('DROP INDEX IDX_C6B5D12C7294869C ON debat');
        $this->addSql('ALTER TABLE debat DROP article_id');
    }
}
