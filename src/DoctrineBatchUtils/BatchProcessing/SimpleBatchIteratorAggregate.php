<?php

declare(strict_types=1);

namespace DoctrineBatchUtils\BatchProcessing;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use IteratorAggregate;
use Throwable;
use Traversable;

use function get_class;
use function is_array;
use function is_object;
use function key;

/**
 * @template A
 * @template B
 */
final class SimpleBatchIteratorAggregate implements IteratorAggregate
{
    /** @var iterable<A, B> */
    private iterable $resultSet;
    private EntityManagerInterface $entityManager;
    /** @psalm-var positive-int */
    private int $batchSize;

    /**
     * @psalm-param positive-int $batchSize
     */
    public static function fromQuery(AbstractQuery $query, int $batchSize): self
    {
        return new self($query->toIterable(), $query->getEntityManager(), $batchSize);
    }

    /**
     * @param array<C, D> $results
     *
     * @template C
     * @template D
     *
     * @psalm-param positive-int $batchSize
     */
    public static function fromArrayResult(array $results, EntityManagerInterface $entityManager, int $batchSize): self
    {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * @param Traversable<E, F> $results
     *
     * @template E
     * @template F
     *
     * @psalm-param positive-int $batchSize
     */
    public static function fromTraversableResult(
        Traversable $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ): self {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * @return Traversable<A, B|mixed>
     */
    public function getIterator(): iterable
    {
        $iteration = 0;
        $resultSet = $this->resultSet;

        $this->entityManager->beginTransaction();

        try {
            foreach ($resultSet as $key => $value) {
                $iteration += 1;

                if (is_array($value)) {
                    $firstKey = key($value);
                    if ($firstKey !== null && is_object($value[$firstKey]) && $value === [$firstKey => $value[$firstKey]]) {
                        yield $key => $this->reFetchObject($value[$firstKey]);

                        $this->flushAndClearBatch($iteration);
                        continue;
                    }
                }

                if (! is_object($value)) {
                    yield $key => $value;

                    $this->flushAndClearBatch($iteration);
                    continue;
                }

                yield $key => $this->reFetchObject($value);

                $this->flushAndClearBatch($iteration);
            }
        } catch (Throwable $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        $this->flushAndClearEntityManager();
        $this->entityManager->commit();
    }

    /**
     * BatchIteratorAggregate constructor (private by design: use a named constructor instead).
     *
     * @param iterable<A, B> $resultSet
     *
     * @psalm-param positive-int $batchSize
     */
    private function __construct(iterable $resultSet, EntityManagerInterface $entityManager, int $batchSize)
    {
        $this->resultSet     = $resultSet;
        $this->entityManager = $entityManager;
        $this->batchSize     = $batchSize;
    }

    private function reFetchObject(object $object): object
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($object));
        /** @psalm-var class-string $classname */
        $classname  = $metadata->getName();
        $freshValue = $this->entityManager->find($classname, $metadata->getIdentifierValues($object));

        if (! $freshValue) {
            throw MissingBatchItemException::fromInvalidReference($metadata, $object);
        }

        return $freshValue;
    }

    private function flushAndClearBatch(int $iteration): void
    {
        if ($iteration % $this->batchSize) {
            return;
        }

        $this->flushAndClearEntityManager();
    }

    private function flushAndClearEntityManager(): void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
