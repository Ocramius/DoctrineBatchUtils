# DoctrineBatchUtils

This repository attempts to ease the pain of dealing with 
[batch-processing](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/batch-processing.html)
in the context of [Doctrine ORM](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/)
transactions.

This repository is maintained by [Patrick Reimers (PReimers)](https://github.com/PReimers).

[![License](https://img.shields.io/packagist/l/ocramius/doctrine-batch-utils.svg)](https://github.com/Ocramius/DoctrineBatchUtils/blob/master/LICENSE)
[![Current release](https://img.shields.io/packagist/v/ocramius/doctrine-batch-utils.svg)](https://packagist.org/packages/ocramius/doctrine-batch-utils)
[![Build Status](https://github.com/Ocramius/DoctrineBatchUtils/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/Ocramius/DoctrineBatchUtils/actions/workflows/continuous-integration.yml)

## Installation

Supported installation method is via [Composer](http://getcomposer.org/):

```sh
composer require ocramius/doctrine-batch-utils
```

## Current features

As it stands, the only implemented utility in this repository is an 
[`IteratorAggregate`](http://php.net/manual/en/class.iteratoraggregate.php) that 
wraps around a DB transaction and calls 
[`ObjectManager#flush()`](https://github.com/doctrine/common/blob/v2.5.1/lib/Doctrine/Common/Persistence/ObjectManager.php#L120)
and [`ObjectManager#clear()`](https://github.com/doctrine/common/blob/v2.5.1/lib/Doctrine/Common/Persistence/ObjectManager.php#L88)
on the given [`EntityManager`](https://github.com/doctrine/doctrine2/blob/v2.5.1/lib/Doctrine/ORM/EntityManagerInterface.php).


#### Example (array iteration)

It can be used as following:

```php
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;

$object1  = $entityManager->find('Foo', 1);
$object2  = $entityManager->find('Bar', 2);

$iterable = SimpleBatchIteratorAggregate::fromArrayResult(
    [$object1, $object2], // items to iterate
    $entityManager,       // the entity manager to operate on
    100                   // items to traverse before flushing/clearing
);

foreach ($iterable as $record) {
    // operate on records here
}
```

##### `$record` freshness

Please note that the `$record` inside the loop will always be "fresh" 
([`managed`](http://doctrine-orm.readthedocs.org/projects/doctrine-orm/en/latest/reference/working-with-objects.html#persisting-entities) state),
as the iterator re-fetches it on its own: this prevents you from having to
manually call [`ObjectManager#find()`](https://github.com/doctrine/common/blob/v2.5.1/lib/Doctrine/Common/Persistence/ObjectManager.php#L42)
on your own for every iteration.

##### Automatic flushing/clearing

In this example, the `EntityManager` will be flushed and cleared only once, 
but if there were more than 100 records, then it would flush (and clear) twice 
or more.

#### Example (query/iterators)

The previous example is still not memory efficient, as we are operating on a
pre-loaded array of objects loaded by the ORM.

We can use queries instead:

```php
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;

$iterable = SimpleBatchIteratorAggregate::fromQuery(
    $entityManager->createQuery('SELECT f FROM Files f'),
    100 // flush/clear after 100 iterations
);

foreach ($iterable as $record) {
    // operate on records here
}
```

Or our own iterator/generator:


```php
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;

// This is where you'd persist/create/load your entities (a lot of them!)
$results = function () {
    for ($i = 0; $i < 100000000; $i += 1) {
        yield new MyEntity($i); // note: identifier must exist in the DB
    }
};
 
$iterable = SimpleBatchIteratorAggregate::fromTraversableResult(
    $results(),
    $entityManager,
    100 // flush/clear after 100 iterations
);

foreach ($iterable as $record) {
    // operate on records here
}

// eventually after all records have been processed, the assembled transaction will be committed to the database
```

Both of these approaches are much more memory efficient.
