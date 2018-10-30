# Upgrade

## 2.0.0

**BC Breaks**

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
