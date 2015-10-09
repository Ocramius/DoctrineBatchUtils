<?php

namespace DoctrineBatchUtilsTest\BatchProcessing;

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

    /**
     * @return void
     */
    public function testFromQuery()
    {
        $this->query->expects(self::any())->method('iterate')->willReturn(new \ArrayIterator());

        self::assertInstanceOf(
            SimpleBatchIteratorAggregate::class,
            SimpleBatchIteratorAggregate::fromQuery($this->query, 100)
        );
    }
}

