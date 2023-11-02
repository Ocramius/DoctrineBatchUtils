# Upgrade

## 2.0.0

### BC Breaks

Access to the actual entity has changed. In previous versions the entity could be accessed via `[0]` on result item like this:

```php
$iterable = SimpleBatchIteratorAggregate::fromArrayResult(...);
foreach ($iterable as $record) {
    $entity = $record[0];
    ...
}
```

That was rather confusing and unexpected so it is no longer wrapped in array and `[0]` acessor must be dropped:

```php
foreach ($iterable as $record) {
    $entity = $record;
    ...
}
```

- The parameter `$batchSize` of `DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate::fromQuery()` changed from no type to a non-contravariant int
- The parameter `$batchSize` of `DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate::fromArrayResult()` changed from no type to a non-contravariant int
- The parameter `$batchSize` of `DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate::fromTraversableResult()` changed from no type to a non-contravariant int
- The return type of `DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException::fromInvalidReference()` changed from no type to `DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException`
- The parameter `$object` of `DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException::fromInvalidReference()` changed from no type to a non-contravariant object
- The parameter `$object` of `DoctrineBatchUtils\BatchProcessing\Exception\MissingBatchItemException::fromInvalidReference()` changed from no type to object
