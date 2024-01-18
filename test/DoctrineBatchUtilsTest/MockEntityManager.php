<?php

declare(strict_types=1);

namespace DoctrineBatchUtilsTest;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class MockEntityManager implements EntityManagerInterface
{
    /** @inheritDoc */
    public function getClassMetadata($className)
    {
        echo __FUNCTION__ . "\n";
    }

    public function getCache(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getConnection(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getExpressionBuilder(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function beginTransaction(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function transactional($func): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function commit(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function rollback(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function createQuery($dql = ''): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function createNamedQuery($name): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function createNativeQuery($sql, ResultSetMapping $rsm): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function createNamedNativeQuery($name): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function createQueryBuilder(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function getReference($entityName, $id): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function getPartialReference($entityName, $identifier): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function close(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function copy($entity, $deep = false): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function lock($entity, $lockMode, $lockVersion = null): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getEventManager(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getConfiguration(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function isOpen(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getUnitOfWork(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function getHydrator($hydrationMode): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function newHydrator($hydrationMode): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getProxyFactory(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getFilters(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function isFiltersStateClean(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function hasFilters(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function find($className, $id)
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function persist($object): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function remove($object): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function clear(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function detach($object): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function refresh($object): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function flush(): void
    {
        echo __FUNCTION__ . "\n";
    }

    /** @inheritDoc */
    public function getRepository($className): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getMetadataFactory(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function initializeObject(object $obj): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function contains(object $object): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function __call(string $name, mixed $arguments): void
    {
        echo __FUNCTION__ . "\n";
    }
}
