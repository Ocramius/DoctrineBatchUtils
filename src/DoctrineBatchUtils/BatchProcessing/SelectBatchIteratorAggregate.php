<?php

declare(strict_types=1);

namespace DoctrineBatchUtils\BatchProcessing;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use IteratorAggregate;
use Traversable;

use function get_class;
use function is_array;
use function is_object;
use function key;

/**
 * 'Read' focused batching iterator that will issue a clear to the entity manager
 * every batch size to 'detach' the managed objects and therefore make them entities only for the
 * purpose of reading without manually attaching them back to the EntityManager.
 *
 * @template TKey
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
final class SelectBatchIteratorAggregate implements IteratorAggregate
{
    /** @var iterable<TKey, TValue> */
    private iterable $resultSet;
    private EntityManagerInterface $entityManager;
    /** @psalm-var positive-int */
    private int $batchSize;

    /** @psalm-param positive-int $batchSize */
    public static function fromQuery(AbstractQuery $query, int $batchSize): self
    {
        return new self($query->toIterable(), $query->getEntityManager(), $batchSize);
    }

    /**
     * @param array<C, D> $results
     * @psalm-param positive-int $batchSize
     *
     * @return self<C, D>
     *
     * @template C
     * @template D
     */
    public static function fromArrayResult(
        array $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ): self {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * @param Traversable<E, F> $results
     * @psalm-param positive-int $batchSize
     *
     * @return self<E, F>
     *
     * @template E
     * @template F
     */
    public static function fromTraversableResult(
        Traversable $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ): self {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * @return Traversable<TKey, TValue>
     *
     * @psalm-suppress InvalidReturnType psalm can't infer the correct key/value pairs here, but we've carefully
     *                                   tested this signature.
     */
    public function getIterator(): Traversable
    {
        $iteration = 0;

        foreach ($this->resultSet as $key => $value) {
            $iteration += 1;

            if (is_array($value)) {
                $firstKey = key($value);
                if ($firstKey !== null && is_object($value[$firstKey]) && $value === [$firstKey => $value[$firstKey]]) {
                    yield $key => $this->reFetchObject($value[$firstKey]);

                    $this->clearBatch($iteration);
                    continue;
                }
            }

            if (! is_object($value)) {
                yield $key => $value;

                $this->clearBatch($iteration);
                continue;
            }

            yield $key => $this->reFetchObject($value);

            $this->clearBatch($iteration);
        }

        $this->entityManager->clear();
    }

    /**
     * BatchIteratorAggregate constructor (private by design: use a named constructor instead).
     *
     * @param iterable<TKey, TValue> $resultSet
     * @psalm-param positive-int $batchSize
     */
    private function __construct(iterable $resultSet, EntityManagerInterface $entityManager, int $batchSize)
    {
        $this->resultSet     = $resultSet;
        $this->entityManager = $entityManager;
        $this->batchSize     = $batchSize;
    }

    /**
     * @psalm-param TReFetched $object
     *
     * @psalm-return TReFetched
     *
     * @template TReFetched of object
     */
    private function reFetchObject(object $object): object
    {
        $className  = get_class($object);
        $metadata   = $this->entityManager->getClassMetadata($className);
        $freshValue = $this->entityManager->find($className, $metadata->getIdentifierValues($object));

        if (! $freshValue) {
            throw MissingBatchItemException::fromInvalidReference($metadata, $object);
        }

        return $freshValue;
    }

    private function clearBatch(int $iteration): void
    {
        if ($iteration % $this->batchSize) {
            return;
        }

        $this->entityManager->clear();
    }
}
