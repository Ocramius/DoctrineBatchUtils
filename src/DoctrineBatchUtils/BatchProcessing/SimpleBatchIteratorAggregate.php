<?php

declare(strict_types=1);

namespace DoctrineBatchUtils\BatchProcessing;

use ArrayIterator;
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

final class SimpleBatchIteratorAggregate implements IteratorAggregate
{
    /** @var Traversable */
    private $resultSet;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var int */
    private $batchSize;

    public static function fromQuery(AbstractQuery $query, int $batchSize) : self
    {
        return new self($query->iterate(), $query->getEntityManager(), $batchSize);
    }

    /**
     * @param mixed[] $results
     */
    public static function fromArrayResult(array $results, EntityManagerInterface $entityManager, int $batchSize) : self
    {
        return new self(new ArrayIterator($results), $entityManager, $batchSize);
    }

    public static function fromTraversableResult(
        Traversable $results,
        EntityManagerInterface $entityManager,
        int $batchSize
    ) : self {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
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
     */
    private function __construct(Traversable $resultSet, EntityManagerInterface $entityManager, int $batchSize)
    {
        $this->resultSet     = $resultSet;
        $this->entityManager = $entityManager;
        $this->batchSize     = $batchSize;
    }

    private function reFetchObject(object $object) : object
    {
        $metadata   = $this->entityManager->getClassMetadata(get_class($object));
        $freshValue = $this->entityManager->find($metadata->getName(), $metadata->getIdentifierValues($object));

        if (! $freshValue) {
            throw MissingBatchItemException::fromInvalidReference($metadata, $object);
        }

        return $freshValue;
    }

    private function flushAndClearBatch(int $iteration) : void
    {
        if ($iteration % $this->batchSize) {
            return;
        }

        $this->flushAndClearEntityManager();
    }

    private function flushAndClearEntityManager() : void
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
