<?php

namespace DoctrineBatchUtilsTest\BatchProcessing;

use ArrayIterator;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
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
}

