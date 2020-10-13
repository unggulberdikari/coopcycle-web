<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201012102523 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    private function createTargets()
    {
        $stmt = $this->connection->prepare('SELECT DISTINCT restaurant_id FROM sylius_order WHERE restaurant_id IS NOT NULL');
        $stmt->execute();

        while ($order = $stmt->fetch()) {
            $this->addSql('INSERT INTO sylius_order_target (restaurant_id) VALUES (:restaurant_id)', [
                'restaurant_id' => $order['restaurant_id'],
            ]);
        }
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->createTargets();

        $this->addSql('ALTER TABLE sylius_order ADD target_id INT DEFAULT NULL');
        $this->addSql('UPDATE sylius_order o SET target_id = t.id FROM sylius_order_target t WHERE o.restaurant_id IS NOT NULL AND o.restaurant_id = t.restaurant_id');

        $this->addSql('ALTER TABLE sylius_order DROP CONSTRAINT fk_6196a1f9b1e7706e');
        $this->addSql('DROP INDEX idx_6196a1f9b1e7706e');
        $this->addSql('ALTER TABLE sylius_order DROP restaurant_id');

        $this->addSql('ALTER TABLE sylius_order ADD CONSTRAINT FK_6196A1F9158E0B66 FOREIGN KEY (target_id) REFERENCES sylius_order_target (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6196A1F9158E0B66 ON sylius_order (target_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE sylius_order ADD restaurant_id INT DEFAULT NULL');
        $this->addSql('UPDATE sylius_order o SET restaurant_id = t.restaurant_id FROM sylius_order_target t WHERE o.target_id IS NOT NULL AND o.target_id = t.id');

        $this->addSql('ALTER TABLE sylius_order DROP CONSTRAINT FK_6196A1F9158E0B66');
        $this->addSql('DROP INDEX IDX_6196A1F9158E0B66');
        $this->addSql('ALTER TABLE sylius_order DROP target_id');

        $this->addSql('ALTER TABLE sylius_order ADD CONSTRAINT fk_6196a1f9b1e7706e FOREIGN KEY (restaurant_id) REFERENCES restaurant (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6196a1f9b1e7706e ON sylius_order (restaurant_id)');
    }
}
