<?php

declare(strict_types=1);

namespace DoctrineBatchUtils\BatchProcessing\Exception;

use Doctrine\Persistence\Mapping\ClassMetadata;
use UnexpectedValueException;

use function get_class;
use function json_encode;
use function spl_object_hash;
use function sprintf;

/**
 * Marker interface for exceptions thrown in the batch processing component
 */
class MissingBatchItemException extends UnexpectedValueException implements ExceptionInterface
{
    public static function fromInvalidReference(ClassMetadata $metadata, object $object): MissingBatchItemException
    {
        return new self(sprintf(
            'Requested batch item %s#%s (of type %s) with identifier "%s" could not be found',
            get_class($object),
            spl_object_hash($object),
            $metadata->getName(),
            json_encode($metadata->getIdentifierValues($object))
        ));
    }
}
