<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

trait DoctrineTestCaseTrait
{
    abstract protected static function getContainer(): ContainerInterface;

    public function entityManager(): EntityManager
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    public function connection(): Connection
    {
        return self::getContainer()->get(Connection::class);
    }

    protected function clearDatabase(): void
    {
        $connection = $this->connection();

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        foreach ($connection->createSchemaManager()->listTableNames() as $tableName) {
            $connection->executeQuery(sprintf('TRUNCATE `%s`', $tableName));
        }
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function clearUnitOfWork(): void
    {
        $this->entityManager()->getUnitOfWork()->clear();
    }
}