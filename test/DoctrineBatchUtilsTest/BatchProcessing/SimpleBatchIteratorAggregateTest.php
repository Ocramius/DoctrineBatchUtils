<?php

declare(strict_types=1);

namespace DoctrineBatchUtilsTest\BatchProcessing;

use ArrayIterator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

use function array_fill;
use function count;

/**
 * @covers \DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate
 */
final class SimpleBatchIteratorAggregateTest extends TestCase
{
    /** @var AbstractQuery|MockObject */
    private $query;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var ClassMetadata|MockObject */
    private $metadata;

    protected function setUp(): void
    {
        $this->query         = $this->createMock(AbstractQuery::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->metadata      = $this->createMock(ClassMetadata::class);

        $this->query->expects(self::any())->method('getEntityManager')->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())->method('getClassMetadata')->willReturn($this->metadata);
        $this->metadata->expects(self::any())->method('getName')->willReturn('Yadda');

        parent::setUp();
    }

    public function testFromQuery(): void
    {
        $this->query->expects(self::any())->method('iterate')->willReturn(new ArrayIterator());

        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromQuery($this->query, 100)
        );
    }

    public function testFromArray(): void
    {
        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100)
        );
    }

    public function testFromTraversableResult(): void
    {
        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromTraversableResult(new ArrayIterator([]), $this->entityManager, 100)
        );
    }

    public function testIterationWithEmptySet(): void
    {
        $iterator = SimpleBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100);

        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('flush');
        $this->entityManager->expects(self::at(2))->method('clear');
        $this->entityManager->expects(self::at(3))->method('commit');

        foreach ($iterator as $key => $value) {
            throw new UnexpectedValueException('Iterator should have been empty!');
        }
    }

    public function testIterationRollsBackOnMissingItems(): void
    {
        $iterator = SimpleBatchIteratorAggregate::fromArrayResult([new stdClass()], $this->entityManager, 100);

        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('rollback');

        $this->expectException(MissingBatchItemException::class);

        foreach ($iterator as $key => $value) {
            $dummy = $key;
        }
    }

    public function testIterationWithNonObjects(): void
    {
        $items = ['foo' => 'bar', 'bar' => 'baz'];

        $iterator = SimpleBatchIteratorAggregate::fromArrayResult($items, $this->entityManager, 100);

        $this->entityManager->expects(self::never())->method('find');
        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('flush');
        $this->entityManager->expects(self::at(2))->method('clear');
        $this->entityManager->expects(self::at(3))->method('commit');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame($items, $iteratedObjects);
    }

    public function testIterationWithSuccessfulReFetches(): void
    {
        $originalObjects = ['foo' => new stdClass(), 'bar' => new stdClass()];
        $freshObjects    = ['foo' => new stdClass(), 'bar' => new stdClass()];

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
     * \Doctrine\ORM\AbstractQuery#iterate() produces nested results like [[entity],[entity],[entity]] instead
     * of a flat [entity,entity,entity], so we have to unwrap the results to refresh them.
     */
    public function testIterationWithSuccessfulReFetchesInNestedIterableResult(): void
    {
        $originalObjects = [[new stdClass()], [new stdClass()]];
        $freshObjects    = [new stdClass(), new stdClass()];

        $iterator = SimpleBatchIteratorAggregate::fromArrayResult($originalObjects, $this->entityManager, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    public function testIterationWithSuccessfulReFetchesInNestedIterableResultFromQuery(): void
    {
        $originalObjects = [[new stdClass()], [new stdClass()]];
        $freshObjects    = [new stdClass(), new stdClass()];

        $this->query->method('iterate')->willReturn(new ArrayIterator($originalObjects));
        $iterator = SimpleBatchIteratorAggregate::fromQuery($this->query, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    public function testIterationWithSuccessfulReFetchesInNestedIterableResultFromTraversableResult(): void
    {
        $originalObjects = [[new stdClass()], [new stdClass()]];
        $freshObjects    = [new stdClass(), new stdClass()];

        $this->query->method('iterate')->willReturn(new ArrayIterator($originalObjects));
        $iterator = SimpleBatchIteratorAggregate::fromTraversableResult(new ArrayIterator($originalObjects), $this->entityManager, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    /**
     * @param stdClass[][] $originalObjects
     * @param stdClass[]   $freshObjects
     */
    private function assertSuccessfulReFetchesInNestedIterableResult(SimpleBatchIteratorAggregate $iterator, array $originalObjects, array $freshObjects): void
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
        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('flush');
        $this->entityManager->expects(self::at(2))->method('clear');
        $this->entityManager->expects(self::at(3))->method('commit');

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
    public function testWillNotReFetchEntitiesInNonIterableAlikeResult(): void
    {
        $originalObjects = [
            [new stdClass(), new stdClass()],
            ['123'],
            [],
        ];

        $iterator = SimpleBatchIteratorAggregate::fromArrayResult($originalObjects, $this->entityManager, 100);

        $this->entityManager->expects(self::never())->method('find');
        $this->entityManager->expects(self::at(0))->method('beginTransaction');
        $this->entityManager->expects(self::at(1))->method('flush');
        $this->entityManager->expects(self::at(2))->method('clear');
        $this->entityManager->expects(self::at(3))->method('commit');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame($originalObjects, $iteratedObjects);
    }

    /**
     * @dataProvider iterationFlushesProvider
     */
    public function testIterationFlushesAtGivenBatchSizes(int $resultItemsCount, int $batchSize, int $expectedFlushesCount): void
    {
        $object = new stdClass();

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
    public function iterationFlushesProvider(): array
    {
        return [
            [10, 5, 3],
            [2, 1, 3],
            [15, 5, 4],
            [10, 2, 6],
        ];
    }
}
