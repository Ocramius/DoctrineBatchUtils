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

class MockEntityManager implements EntityManagerInterface
{
    private EntityManagerInterface $realEntityManager;

    public function __construct(EntityManager $realEntityManager)
    {
        $this->realEntityManager = $realEntityManager;
    }

    public function isUninitializedObject($value) {
        echo __FUNCTION__ . "\n";
    }

    public function getProxyFactory(): ProxyFactory
    {
        $config = $this->realEntityManager->getConfiguration();

        return new ProxyFactory(
            $this,
            $config->getProxyDir(),
            $config->getProxyNamespace(),
        );
    }

    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->realEntityManager->getMetadataFactory();
    }

    public function getClassMetadata(string $className): ClassMetadata
    {
        return $this->realEntityManager->getClassMetadata($className);
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->realEntityManager->getUnitOfWork();
    }

    public function getCache(): Cache|null
    {
        return $this->realEntityManager->getCache();
    }

    public function getConnection(): Connection
    {
        return $this->realEntityManager->getConnection();
    }

    public function getExpressionBuilder(): Expr
    {
        return $this->realEntityManager->getExpressionBuilder();
    }

    public function beginTransaction(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function wrapInTransaction(callable $func): mixed
    {
        return $this->realEntityManager->wrapInTransaction($func);
    }

    public function commit(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function rollback(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function createQuery(string $dql = ''): Query
    {
        return $this->realEntityManager->createQuery($dql);
    }

    public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
    {
        return $this->realEntityManager->createNativeQuery($sql, $rsm);
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->realEntityManager->createQueryBuilder();
    }

    public function getReference(string $entityName, mixed $id): object|null
    {
        return $this->realEntityManager->getReference($entityName, $id);
    }

    public function close(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function lock(object $entity, LockMode|int $lockMode, DateTimeInterface|int|null $lockVersion = null): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getEventManager(): EventManager
    {
        return $this->realEntityManager->getEventManager();
    }

    public function getConfiguration(): Configuration
    {
        return $this->realEntityManager->getConfiguration();
    }

    public function isOpen(): bool
    {
        return $this->realEntityManager->isOpen();
    }

    /**
     * {@inheritDoc}
     */
    public function newHydrator($hydrationMode): AbstractHydrator
    {
        return $this->realEntityManager->newHydrator($hydrationMode);
    }

    public function getFilters(): FilterCollection
    {
        return $this->realEntityManager->getFilters();
    }

    public function isFiltersStateClean(): bool
    {
        return $this->realEntityManager->isFiltersStateClean();
    }

    public function hasFilters(): bool
    {
        return $this->realEntityManager->hasFilters();
    }

    public function find(string $className, mixed $id, LockMode|int|null $lockMode = null, int|null $lockVersion = null): object|null
    {
        return $this->realEntityManager->find($className, $id, $lockMode, $lockVersion);
    }

    public function persist(object $object): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function remove(object $object): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function clear(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function detach(object $object): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function refresh(object $object, LockMode|int|null $lockMode = null): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function flush(): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function getRepository(string $className): EntityRepository
    {
        return $this->realEntityManager->getRepository($className);
    }

    public function initializeObject(object $obj): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function contains(object $object): bool
    {
        return $this->realEntityManager->contains($object);
    }
}