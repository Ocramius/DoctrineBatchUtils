<?php

namespace DoctrineBatchUtils\BatchProcessing;

use ArrayIterator;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use IteratorAggregate;
use Traversable;

final class SimpleBatchIteratorAggregate implements IteratorAggregate
{
    /**
     * @var Traversable
     */
    private $resultSet;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var
     */
    private $batchSize;

    /**
     * @param AbstractQuery $query
     * @param int           $batchSize
     *
     * @return self
     */
    public static function fromQuery(AbstractQuery $query, $batchSize)
    {
        return new self($query->iterate(), $query->getEntityManager(), $batchSize);
    }

    /**
     * @param object[]               $results
     * @param EntityManagerInterface $entityManager
     * @param int                    $batchSize
     *
     * @return self
     */
    public static function fromArrayResult(array $results, EntityManagerInterface $entityManager, $batchSize)
    {
        return new self(new ArrayIterator($results), $entityManager, $batchSize);
    }

    /**
     * @param Traversable            $results
     * @param EntityManagerInterface $entityManager
     * @param                        $batchSize
     *
     * @return self
     */
    public static function fromTraversableResult(
        Traversable $results,
        EntityManagerInterface $entityManager,
        $batchSize
    ) {
        return new self($results, $entityManager, $batchSize);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        $iteration = 0;
        $resultSet = clone $this->resultSet;

        $this->entityManager->beginTransaction();

        try {
            foreach ($resultSet as $key => $value) {
                ++$iteration;

                if (! is_object($value)) {
                    yield $key => $value;

                    $this->flushAndClearBatch($iteration);
                    continue;
                }

                yield $key => $this->reFetchObject($value);

                $this->flushAndClearBatch($iteration);
            }
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }

        $this->flushAndClearEntityManager();
        $this->entityManager->commit();
    }

    /**
     * BatchIteratorAggregate constructor (private by design: use a named constructor instead).
     *
     * @param Traversable            $resultSet
     * @param EntityManagerInterface $entityManager
     * @param                        $batchSize
     */
    private function __construct(Traversable $resultSet, EntityManagerInterface $entityManager, $batchSize)
    {
        $this->resultSet     = $resultSet;
        $this->entityManager = $entityManager;
        $this->batchSize     = $batchSize;
    }

    /**
     * @param object $object
     *
     * @return object
     */
    private function reFetchObject($object)
    {
        $metadata   = $this->entityManager->getClassMetadata(get_class($object));
        $freshValue = $this->entityManager->find($metadata->getName(), $metadata->getIdentifierValues($object));

        if (! $freshValue) {
            throw MissingBatchItemException::fromInvalidReference($metadata, $object);
        }

        return $freshValue;
    }

    /**
     * @param int $iteration
     *
     * @return void
     */
    private function flushAndClearBatch($iteration)
    {
        if ($iteration % $this->batchSize) {
            return;
        }

        $this->flushAndClearEntityManager();
    }

    /**
     * @return void
     */
    private function flushAndClearEntityManager()
    {
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}

