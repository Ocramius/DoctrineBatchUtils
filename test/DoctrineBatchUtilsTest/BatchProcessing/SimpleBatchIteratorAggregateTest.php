<?php

declare(strict_types=1);

namespace DoctrineBatchUtilsTest\BatchProcessing;

use ArrayIterator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;
use DoctrineBatchUtilsTest\MockEntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

use function array_fill;
use function count;

/** @covers \DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate */
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
        $entityManager       = $this->getMockBuilder(MockEntityManager::class);
        $entityManager       = $entityManager->disableOriginalConstructor();
        $entityManager       = $entityManager->onlyMethods(['getClassMetadata', 'find']);
        $entityManager       = $entityManager->disableOriginalClone();
        $entityManager       = $entityManager->disableArgumentCloning();
        $entityManager       = $entityManager->disallowMockingUnknownTypes();
        $this->entityManager = $entityManager->getMock();
        $this->metadata      = $this->createMock(ClassMetadata::class);
        $this->query         = $this->createMock(AbstractQuery::class);

        $this->query->expects(self::any())->method('getEntityManager')->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())->method('getClassMetadata')->willReturn($this->metadata);
        $this->metadata->expects(self::any())->method('getName')->willReturn('Yadda');

        parent::setUp();
    }

    public function testFromQuery(): void
    {
        $this->query->expects(self::any())->method('toIterable')->willReturn(new ArrayIterator());

        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromQuery($this->query, 100),
        );
    }

    public function testFromArray(): void
    {
        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100),
        );
    }

    public function testFromTraversableResult(): void
    {
        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromTraversableResult(new ArrayIterator([]), $this->entityManager, 100),
        );
    }

    public function testIterationWithEmptySet(): void
    {
        $iterator = SimpleBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100);

        $this->expectOutputString("beginTransaction\nflush\nclear\ncommit\n");

        foreach ($iterator as $key => $value) {
            throw new UnexpectedValueException('Iterator should have been empty!');
        }
    }

    /** @uses \DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException */
    public function testIterationRollsBackOnMissingItems(): void
    {
        $iterator = SimpleBatchIteratorAggregate::fromArrayResult([new stdClass()], $this->entityManager, 100);

        $this->expectOutputString("beginTransaction\nrollback\n");

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

        $this->expectOutputString("beginTransaction\nflush\nclear\ncommit\n");

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

        $this->expectOutputString("beginTransaction\nflush\nclear\ncommit\n");

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

        $this->query->method('toIterable')->willReturn(new ArrayIterator($originalObjects));
        $iterator = SimpleBatchIteratorAggregate::fromQuery($this->query, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    public function testIterationWithSuccessfulReFetchesInNestedIterableResultFromTraversableResult(): void
    {
        $originalObjects = [[new stdClass()], [new stdClass()]];
        $freshObjects    = [new stdClass(), new stdClass()];

        $this->query->method('toIterable')->willReturn(new ArrayIterator($originalObjects));
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
            ],
        );
        $this->entityManager->expects(self::exactly(count($originalObjects)))->method('find')->willReturnMap(
            [
                ['Yadda', ['id' => 123], $freshObjects[0]],
                ['Yadda', ['id' => 456], $freshObjects[1]],
            ],
        );

        $iteratedObjects = [];

        $this->expectOutputString("beginTransaction\nflush\nclear\ncommit\n");

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
        $this->expectOutputString("beginTransaction\nflush\nclear\ncommit\n");

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertSame($originalObjects, $iteratedObjects);
    }

    /**
     * @psalm-param positive-int $batchSize
     *
     * @dataProvider iterationFlushesProvider
     */
    public function testIterationFlushesAtGivenBatchSizes(int $resultItemsCount, int $batchSize, string $expectOutputString): void
    {
        $object = new stdClass();

        $iterator = SimpleBatchIteratorAggregate::fromArrayResult(
            array_fill(0, $resultItemsCount, $object),
            $this->entityManager,
            $batchSize,
        );

        $this->metadata->expects(self::any())->method('getIdentifierValues')->willReturn(['id' => 123]);
        $this->entityManager->expects(self::exactly($resultItemsCount))->method('find')->willReturn($object);

        $this->expectOutputString($expectOutputString);

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertCount($resultItemsCount, $iteratedObjects);
    }

    /** @return array<int, array<int, int|string>> */
    public static function iterationFlushesProvider(): array
    {
        return [
            [10, 5, "beginTransaction\nflush\nclear\nflush\nclear\nflush\nclear\ncommit\n"],
            [2, 1, "beginTransaction\nflush\nclear\nflush\nclear\nflush\nclear\ncommit\n"],
            [15, 5, "beginTransaction\nflush\nclear\nflush\nclear\nflush\nclear\nflush\nclear\ncommit\n"],
            [10, 2, "beginTransaction\nflush\nclear\nflush\nclear\nflush\nclear\nflush\nclear\nflush\nclear\nflush\nclear\ncommit\n"],
        ];
    }
}
