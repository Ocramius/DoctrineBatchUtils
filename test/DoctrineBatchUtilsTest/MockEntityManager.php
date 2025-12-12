<?php

declare(strict_types=1);

namespace DoctrineBatchUtilsTest;

use DateTimeInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Override;

class MockEntityManager implements EntityManagerInterface
{
    private EntityManagerInterface $realEntityManager;

    public function __construct(EntityManager $realEntityManager)
    {
        $this->realEntityManager = $realEntityManager;
    }

    #[Override]
    public function isUninitializedObject(mixed $value): bool
    {
        echo __FUNCTION__ . "\n";

        return false;
    }

    #[Override]
    public function getProxyFactory(): ProxyFactory
    {
        return $this->realEntityManager->getProxyFactory();
    }

    #[Override]
    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->realEntityManager->getMetadataFactory();
    }

    #[Override]
    public function getClassMetadata(string $className): ClassMetadata
    {
        return $this->realEntityManager->getClassMetadata($className);
    }

    #[Override]
    public function getUnitOfWork(): UnitOfWork
    {
        return $this->realEntityManager->getUnitOfWork();
    }

    #[Override]
    public function getCache(): Cache|null
    {
        return $this->realEntityManager->getCache();
    }

    #[Override]
    public function getConnection(): Connection
    {
        return $this->realEntityManager->getConnection();
    }

    #[Override]
    public function getExpressionBuilder(): Expr
    {
        return $this->realEntityManager->getExpressionBuilder();
    }

    #[Override]
    public function beginTransaction(): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function wrapInTransaction(callable $func): mixed
    {
        return $this->realEntityManager->wrapInTransaction($func);
    }

    #[Override]
    public function commit(): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function rollback(): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function createQuery(string $dql = ''): Query
    {
        return $this->realEntityManager->createQuery($dql);
    }

    #[Override]
    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        return $this->realEntityManager->createNativeQuery($sql, $rsm);
    }

    #[Override]
    public function createQueryBuilder(): QueryBuilder
    {
        return $this->realEntityManager->createQueryBuilder();
    }

    #[Override]
    public function getReference(string $entityName, mixed $id): object|null
    {
        return $this->realEntityManager->getReference($entityName, $id);
    }

    #[Override]
    public function close(): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function lock(object $entity, LockMode|int $lockMode, DateTimeInterface|int|null $lockVersion = null): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function getEventManager(): EventManager
    {
        return $this->realEntityManager->getEventManager();
    }

    #[Override]
    public function getConfiguration(): Configuration
    {
        return $this->realEntityManager->getConfiguration();
    }

    #[Override]
    public function isOpen(): bool
    {
        return $this->realEntityManager->isOpen();
    }

    #[Override]
    public function newHydrator($hydrationMode): AbstractHydrator
    {
        return $this->realEntityManager->newHydrator($hydrationMode);
    }

    #[Override]
    public function getFilters(): FilterCollection
    {
        return $this->realEntityManager->getFilters();
    }

    #[Override]
    public function isFiltersStateClean(): bool
    {
        return $this->realEntityManager->isFiltersStateClean();
    }

    #[Override]
    public function hasFilters(): bool
    {
        return $this->realEntityManager->hasFilters();
    }

    #[Override]
    public function find(string $className, mixed $id, LockMode|int|null $lockMode = null, int|null $lockVersion = null): object|null
    {
        return $this->realEntityManager->find($className, $id, $lockMode, $lockVersion);
    }

    #[Override]
    public function persist(object $object): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function remove(object $object): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function clear(): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function detach(object $object): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function refresh(object $object, LockMode|int|null $lockMode = null): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function flush(): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function getRepository(string $className): EntityRepository
    {
        return $this->realEntityManager->getRepository($className);
    }

    #[Override]
    public function initializeObject(object $obj): void
    {
        echo __FUNCTION__ . "\n";
    }

    #[Override]
    public function contains(object $object): bool
    {
        return $this->realEntityManager->contains($object);
    }
}
