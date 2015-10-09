<?php

namespace DoctrineBatchUtils\BatchProcessing\Exception;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * Marker interface for exceptions thrown in the batch processing component
 */
class MissingBatchItemException extends \UnexpectedValueException implements ExceptionInterface
{
    /**
     * @param ClassMetadata $metadata
     * @param object        $object
     *
     * @return MissingBatchItemException
     */
    public static function fromInvalidReference(ClassMetadata $metadata, $object)
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