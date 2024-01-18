<?php

declare(strict_types=1);

namespace DoctrineBatchUtilsTest\BatchProcessing;

use ArrayIterator;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use DoctrineBatchUtils\BatchProcessing\SelectBatchIteratorAggregate;
use DoctrineBatchUtilsTest\MockEntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use UnexpectedValueException;

use function array_fill;
use function count;

/** @covers \DoctrineBatchUtils\BatchProcessing\SelectBatchIteratorAggregate */
final class SelectBatchIteratorAggregateTest extends TestCase
{
    /** @var AbstractQuery&MockObject */
    private AbstractQuery $query;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var ClassMetadata&MockObject */
    private ClassMetadata $metadata;

    protected function setUp(): void
    {
        $this->query         = $this->createMock(AbstractQuery::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->metadata      = $this->createMock(ClassMetadata::class);

        $this->entityManager->expects(self::never())->method('flush');
        $this->query->method('getEntityManager')->willReturn($this->entityManager);
        $this->entityManager->method('getClassMetadata')->willReturn($this->metadata);
        $this->metadata->method('getName')->willReturn('Yadda');

        parent::setUp();
    }

    public function testFromQuery(): void
    {
        $this->query->method('toIterable')->willReturn(new ArrayIterator());

        self::assertInstanceOf(
            SelectBatchIteratorAggregate::class,
            SelectBatchIteratorAggregate::fromQuery($this->query, 100),
        );
    }

    public function testFromArray(): void
    {
        self::assertInstanceOf(
            SelectBatchIteratorAggregate::class,
            SelectBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100),
        );
    }

    public function testFromTraversableResult(): void
    {
        self::assertInstanceOf(
            SelectBatchIteratorAggregate::class,
            SelectBatchIteratorAggregate::fromTraversableResult(new ArrayIterator([]), $this->entityManager, 100),
        );
    }

    public function testIterationWithEmptySet(): void
    {
        $iterator = SelectBatchIteratorAggregate::fromArrayResult([], $this->entityManager, 100);

        $this->entityManager->expects(self::exactly(1))->method('clear');

        foreach ($iterator as $key => $value) {
            throw new UnexpectedValueException('Iterator should have been empty!');
        }
    }

    public function testIterationWithNonObjects(): void
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

    public function testIterationWithSuccessfulReFetches(): void
    {
        $originalObjects = ['foo' => new stdClass(), 'bar' => new stdClass()];
        $freshObjects    = ['foo' => new stdClass(), 'bar' => new stdClass()];

        $query         = $this->createMock(AbstractQuery::class);
        $metadata      = $this->createMock(ClassMetadata::class);
        $entityManager = new class ($metadata, $freshObjects) extends MockEntityManager {
            private ClassMetadata $classMetadata;
            /** @var array<non-empty-string, object> */
            private array $freshObjects;
            private int $atFind;

            /** @param array<non-empty-string, object> $freshObjects */
            public function __construct(ClassMetadata $classMetadata, array $freshObjects)
            {
                $this->classMetadata = $classMetadata;
                $this->freshObjects  = $freshObjects;
                $this->atFind        = 0;
            }

            /** @inheritDoc */
            public function getClassMetadata($className)
            {
                echo __FUNCTION__ . "\n";

                return $this->classMetadata;
            }

            /** @inheritDoc */
            public function find($className, $id)
            {
                echo __FUNCTION__ . "\n";
                $this->atFind++;

                if ($this->atFind === 1) {
                    TestCase::assertSame('Yadda', $className);

                    TestCase::assertSame(['id' => 123], $id);

                    return $this->freshObjects['foo'];
                }

                if ($this->atFind === 2) {
                    TestCase::assertSame('Yadda', $className);

                    TestCase::assertSame(['id' => 456], $id);

                    return $this->freshObjects['bar'];
                }

                throw new RuntimeException('should not be call more than twice');
            }
        };

        $query->method('getEntityManager')->willReturn($entityManager);
        $metadata->method('getName')->willReturn('Yadda');
        $metadata->method('getIdentifierValues')->willReturnMap([
            [$originalObjects['foo'], ['id' => 123]],
            [$originalObjects['bar'], ['id' => 456]],
        ]);
        $iterator = SelectBatchIteratorAggregate::fromArrayResult($originalObjects, $entityManager, 100);

        $this->expectOutputString("getClassMetadata\nfind\ngetClassMetadata\nfind\nclear\n");

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

        $iterator = SelectBatchIteratorAggregate::fromArrayResult($originalObjects, $this->entityManager, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    public function testIterationWithSuccessfulReFetchesInNestedIterableResultFromQuery(): void
    {
        $originalObjects = [[new stdClass()], [new stdClass()]];
        $freshObjects    = [new stdClass(), new stdClass()];

        $this->query->method('toIterable')->willReturn(new ArrayIterator($originalObjects));
        $iterator = SelectBatchIteratorAggregate::fromQuery($this->query, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    public function testIterationWithSuccessfulReFetchesInNestedIterableResultFromTraversableResult(): void
    {
        $originalObjects = [[new stdClass()], [new stdClass()]];
        $freshObjects    = [new stdClass(), new stdClass()];

        $this->query->method('toIterable')->willReturn(new ArrayIterator($originalObjects));
        $iterator = SelectBatchIteratorAggregate::fromTraversableResult(new ArrayIterator($originalObjects), $this->entityManager, 100);

        $this->assertSuccessfulReFetchesInNestedIterableResult($iterator, $originalObjects, $freshObjects);
    }

    /**
     * @param stdClass[][] $originalObjects
     * @param stdClass[]   $freshObjects
     */
    private function assertSuccessfulReFetchesInNestedIterableResult(SelectBatchIteratorAggregate $iterator, array $originalObjects, array $freshObjects): void
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
        $this->entityManager->expects(self::once())->method('clear');

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
     * @psalm-param positive-int $batchSize
     *
     * @dataProvider iterationClearsProvider
     */
    public function testIterationClearsAtGivenBatchSizes(int $resultItemsCount, int $batchSize, int $expectedClearsCount): void
    {
        $object = new stdClass();

        $iterator = SelectBatchIteratorAggregate::fromArrayResult(
            array_fill(0, $resultItemsCount, $object),
            $this->entityManager,
            $batchSize,
        );

        $this->metadata->method('getIdentifierValues')->willReturn(['id' => 123]);
        $this->entityManager->expects(self::exactly($resultItemsCount))->method('find')->willReturn($object);
        $this->entityManager->expects(self::exactly($expectedClearsCount))->method('clear');

        $iteratedObjects = [];

        foreach ($iterator as $key => $value) {
            $iteratedObjects[$key] = $value;
        }

        $this->assertCount($resultItemsCount, $iteratedObjects);
    }

    /** @return int[][] */
    public function iterationClearsProvider(): array
    {
        return [
            [10, 5, 3],
            [2, 1, 3],
            [15, 5, 4],
            [10, 2, 6],
        ];
    }
}
