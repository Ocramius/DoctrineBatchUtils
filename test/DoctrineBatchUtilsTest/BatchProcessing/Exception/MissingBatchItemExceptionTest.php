<?php

declare(strict_types=1);

namespace DoctrineBatchUtilsTest\BatchProcessing\Exception;

use Doctrine\Persistence\Mapping\ClassMetadata;
use DoctrineBatchUtils\BatchProcessing\Exception\ExceptionInterface;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

use function spl_object_hash;

/** @covers \DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException */
final class MissingBatchItemExceptionTest extends TestCase
{
    public function testFromInvalidReference(): void
    {
        $object   = new stdClass();
        $metadata = $this->createMock(ClassMetadata::class);

        $metadata->expects(self::any())->method('getName')->willReturn('Foo');
        $metadata->expects(self::any())->method('getIdentifierValues')->with($object)->willReturn(['abc' => 'def']);

        $exception = MissingBatchItemException::fromInvalidReference($metadata, $object);

        $this->assertInstanceOf(MissingBatchItemException::class, $exception);
        $this->assertInstanceOf(UnexpectedValueException::class, $exception);
        $this->assertInstanceOf(ExceptionInterface::class, $exception);

        self::assertSame(
            'Requested batch item stdClass#'
            . spl_object_hash($object)
            . ' (of type Foo) with identifier "{"abc":"def"}" could not be found',
            $exception->getMessage(),
        );
    }
}
