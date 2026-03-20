<?php

declare(strict_types=1);

namespace DoctrineBatchUtilsTest\BatchProcessing\Exception;

use Doctrine\Persistence\Mapping\ClassMetadata;
use DoctrineBatchUtils\BatchProcessing\Exception\ExceptionInterface;
use DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

use function spl_object_hash;

#[CoversClass(MissingBatchItemException::class)]
final class MissingBatchItemExceptionTest extends TestCase
{
    public function testFromInvalidReference(): void
    {
        $object   = new stdClass();
        $metadata = $this->createMock(ClassMetadata::class);

        $metadata->method('getName')->willReturn('Foo');
        $metadata->method('getIdentifierValues')->with($object)->willReturn(['abc' => 'def']);

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
