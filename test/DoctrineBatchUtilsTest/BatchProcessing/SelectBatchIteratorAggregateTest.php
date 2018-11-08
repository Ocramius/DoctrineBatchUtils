<?php

namespace DoctrineBatchUtilsTest\BatchProcessing;

use ArrayIterator;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineBatchUtils\BatchProcessing\SelectBatchIteratorAggregate;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \DoctrineBatchUtils\BatchProcessing\SelectBatchIteratorAggregate
 */
final class SelectBatchIteratorAggregateTest extends TestCase
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
        $this->query         = $this->createMock(AbstractQuery::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->metadata      = $this->createMock(ClassMetadata::class);

        $this->entityManager->expects(self::never())->method('flush');
        $this->query->expects(self::any())->method('getEntityManager')->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())->method('getClassMetadata')->willReturn($this->metadata);
        $this->metadata->expects(self::any())->method('getName')->willReturn('Yadda');

        parent::setUp();
    }

    public function testFromQuery()
    {
        $this->query->expects(self::any())->method('iterate')->willReturn(new ArrayIterator());

        self::assertInstanceOf(
            SelectBatchIteratorAggregate::class,
            SelectBatchIteratorAggregate::fromQuery($this->query, 100)
        );
    }

    public function testFromArray()
    {
        self::assertInstanceOf(
            SelectBatchIteratorAggregate::class,
            SelectBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100)
        );
    }

    public function testFromTraversableResult()
    {
        self::assertInstanceOf(
            SelectBatchIteratorAggregate::class,
            SelectBatchIteratorAggregate::fromTraversableResult(new ArrayIterator([]), $this->entityManager, 100)
        );
    }

    public function testIterationWithEmptySet()
    {
        $iterator = SelectBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100);

        $this->entityManager->expects(self::exactly(1))->method('clear');

        foreach ($iterator as $key => $value) {
            throw new \UnexpectedValueException('Iterator should have been empty!');
        }
    }

    public function testIterationWithNonObjects()
    {
        $items = ['foo' => 'bar', 'bar' => 'baz'];

        $iterator = SelectBatchIteratorAggregate::fromArrayResult($items, $this->entityManager, 100);

        $this->entityManager->expects(self::never())->method('find');
        $this->entityManager->expects(self::exactly(1))->method('clear');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame($items, $iteratedObjects);
    }

    public function testIterationWithSuccessfulReFetches()
    {
        $originalObjects = ['foo' => new \stdClass(), 'bar' => new \stdClass()];
        $freshObjects    = ['foo' => new \stdClass(), 'bar' => new \stdClass()];

        $iterator = SelectBatchIteratorAggregate::fromArrayResult($originalObjects, $this->entityManager, 100);

        $this->metadata->expects(self::any())->method('getIdentifierValues')->willReturnMap([
            [$originalObjects['foo'], ['id' => 123]],
            [$originalObjects['bar'], ['id' => 456]],
        ]);
        $this->entityManager->expects(self::exactly(count($originalObjects)))->method('find')->willReturnMap([
            ['Yadda', ['id' => 123], $freshObjects['foo']],
            ['Yadda', ['id' => 456], $freshObjects['bar']],
        ]);
        $this->entityManager->expects(self::at(2))->method('clear');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame($freshObjects, $iteratedObjects);
    }

    /**
     * \Doctrine\ORM\AbstractQuery#iterate() produces nested results like [[entity],[entity],[entity]] instead
     * of a flat [entity,entity,entity], so we have to unwrap the results to refresh them.
     */
    public function testIterationWithSuccessfulReFetchesInNestedIterableResult() : void
    {
        $originalObjects = [[new \stdClass()], [new \stdClass()]];
        $freshObjects    = [new \stdClass(), new \stdClass()];

        $iterator = SelectBatchIteratorAggregate::fromArrayResult($originalObjects, $this->entityManager, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    public function testIterationWithSuccessfulReFetchesInNestedIterableResultFromQuery() : void
    {
        $originalObjects = [[new \stdClass()], [new \stdClass()]];
        $freshObjects    = [new \stdClass(), new \stdClass()];

        $this->query->method('iterate')->willReturn(new ArrayIterator($originalObjects));
        $iterator = SelectBatchIteratorAggregate::fromQuery($this->query, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    public function testIterationWithSuccessfulReFetchesInNestedIterableResultFromTraversableResult() : void
    {
        $originalObjects = [[new \stdClass()], [new \stdClass()]];
        $freshObjects    = [new \stdClass(), new \stdClass()];

        $this->query->method('iterate')->willReturn(new ArrayIterator($originalObjects));
        $iterator = SelectBatchIteratorAggregate::fromTraversableResult(new ArrayIterator($originalObjects), $this->entityManager, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    /**
     * @param stdClass[][] $originalObjects
     * @param stdClass[] $freshObjects
     */
    private function assertSuccessfulReFetchesInNestedIterableResult(SelectBatchIteratorAggregate $iterator, array $originalObjects, array $freshObjects)
    {
        $this->metadata->method('getIdentifierValues')->willReturnMap(
            [
                [$originalObjects[0][0], ['id' => 123]],
                [$originalObjects[1][0], ['id' => 456]],
            ]
        );
        $this->entityManager->expects(self::exactly(count($originalObjects)))->method('find')->willReturnMap(
            [
                ['Yadda', ['id' => 123], $freshObjects[0]],
                ['Yadda', ['id' => 456], $freshObjects[1]],
            ]
        );
        $this->entityManager->expects(self::at(2))->method('clear');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame([$freshObjects[0], $freshObjects[1]], $iteratedObjects);
    }

    /**
     * \Doctrine\ORM\AbstractQuery#iterate() produces nested results like [[entity],[entity],[entity]] instead
     * of a flat [entity,entity,entity], so we have to skip any entries that do not look like those.
     */
    public function testWillNotReFetchEntitiesInNonIterableAlikeResult()
    {
        $originalObjects = [
            [new \stdClass(), new \stdClass()],
            ['123'],
            [],
        ];

        $iterator = SelectBatchIteratorAggregate::fromArrayResult($originalObjects, $this->entityManager, 100);

        $this->entityManager->expects(self::never())->method('find');
        $this->entityManager->expects(self::exactly(1))->method('clear');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame($originalObjects, $iteratedObjects);
    }

    /**
     * @dataProvider iterationClearsProvider
     *
     * @param int $resultItemsCount
     * @param int $batchSize
     * @param int $expectedClearsCount
     */
    public function testIterationClearsAtGivenBatchSizes($resultItemsCount, $batchSize, $expectedClearsCount)
    {
        $object = new \stdClass();

        $iterator = SelectBatchIteratorAggregate::fromArrayResult(
            array_fill(0, $resultItemsCount, $object),
            $this->entityManager,
            $batchSize
        );

        $this->metadata->expects(self::any())->method('getIdentifierValues')->willReturn(['id' => 123]);
        $this->entityManager->expects(self::exactly($resultItemsCount))->method('find')->willReturn($object);
        $this->entityManager->expects(self::exactly($expectedClearsCount))->method('clear');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertCount($resultItemsCount, $iteratedObjects);
    }

    /**
     * @return int[][]
     */
    public function iterationClearsProvider()
    {
        return [
            [10, 5, 3],
            [2, 1, 3],
            [15, 5, 4],
            [10, 2, 6],
        ];
    }
}
