<?php

namespace DoctrineBatchUtilsTest\BatchProcessing;

use ArrayIterator;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use PHPUnit_Framework_TestCase;

/**
 * @covers \DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate
 */
final class SimpleBatchIteratorAggregateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject
     */
    private $query;

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadata;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->query         = $this->getMock(AbstractQuery::class, [], [], '', false);
        $this->entityManager = $this->getMock(EntityManagerInterface::class);
        $this->metadata      = $this->getMock(ClassMetadata::class);

        $this->query->expects(self::any())->method('getEntityManager')->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())->method('getClassMetadata')->willReturn($this->metadata);
        $this->metadata->expects(self::any())->method('getName')->willReturn('Yadda');

        parent::setUp();
    }

    public function testFromQuery()
    {
        $this->query->expects(self::any())->method('iterate')->willReturn(new ArrayIterator());

        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromQuery($this->query, 100)
        );
    }

    public function testFromArray()
    {
        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100)
        );
    }

    public function testFromTraversableResult()
    {
        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromTraversableResult(new ArrayIterator([]), $this->entityManager, 100)
        );
    }

    public function testIterationWithEmptySet()
    {
        $iterator = SimpleBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100);

        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('flush');
        $this->entityManager->expects(self::at(2))->method('clear');
        $this->entityManager->expects(self::at(3))->method('commit');

        foreach ($iterator as $key => $value) {
            throw new \UnexpectedValueException('Iterator should have been empty!');
        }
    }

    public function testIterationRollsBackOnMissingItems()
    {
        $iterator = SimpleBatchIteratorAggregate::fromArrayResult([new \stdClass()], $this->entityManager, 100);

        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('rollback');

        $this->setExpectedException(MissingBatchItemException::class);

        foreach ($iterator as $key => $value) {
        }
    }

    public function testIterationWithSuccessfulReFetches()
    {
        $originalObjects = ['foo' => new \stdClass(), 'bar' => new \stdClass()];
        $freshObjects    = ['foo' => new \stdClass(), 'bar' => new \stdClass()];

        $iterator = SimpleBatchIteratorAggregate::fromArrayResult($originalObjects, $this->entityManager, 100);

        $this->metadata->expects(self::any())->method('getIdentifierValues')->willReturnMap([
            [$originalObjects['foo'], ['id' => 123]],
            [$originalObjects['bar'], ['id' => 456]],
        ]);
        $this->entityManager->expects(self::exactly(count($originalObjects)))->method('find')->willReturnMap([
            ['Yadda', ['id' => 123], $freshObjects['foo']],
            ['Yadda', ['id' => 456], $freshObjects['bar']],
        ]);
        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('flush');
        $this->entityManager->expects(self::at(2))->method('clear');
        $this->entityManager->expects(self::at(3))->method('commit');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame($freshObjects, $iteratedObjects);
    }

    /**
     * @dataProvider iterationFlushesProvider
     *
     * @param int $resultItemsCount
     * @param int $batchSize
     * @param int $expectedFlushesCount
     */
    public function testIterationFlushesAtGivenBatchSizes($resultItemsCount, $batchSize, $expectedFlushesCount)
    {
        $object = new \stdClass();
        $values = array_fill(0, $resultItemsCount, $object);

        $iterator = SimpleBatchIteratorAggregate::fromArrayResult(
            array_fill(0, $resultItemsCount, $object),
            $this->entityManager,
            $batchSize
        );

        $this->metadata->expects(self::any())->method('getIdentifierValues')->willReturn(['id' => 123]);
        $this->entityManager->expects(self::exactly($resultItemsCount))->method('find')->willReturn($object);
        $this->entityManager->expects(self::exactly($expectedFlushesCount))->method('flush');
        $this->entityManager->expects(self::exactly($expectedFlushesCount))->method('clear');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertCount($resultItemsCount, $iteratedObjects);
    }

    /**
     * @return int[][]
     */
    public function iterationFlushesProvider()
    {
        return [
            [10, 5, 3],
            [2, 1, 3],
            [15, 5, 4],
            [10, 2, 6],
        ];
    }
}

