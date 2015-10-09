# DoctrineBatchUtils

This repository attempts to ease the pain of dealing with 
[batch-processing](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/batch-processing.html)
in the context of [Doctrine ORM](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/)
transactions.

![License](https://img.shields.io/packagist/l/ocramius/doctrine-batch-utils.svg)
![Current release](https://img.shields.io/packagist/v/ocramius/doctrine-batch-utils.svg)
![Travis-CI build status](https://img.shields.io/travis/Ocramius/DoctrineBatchUtils.svg)

### Current features

As it stands, the only implemented utility in this repository is an 
[`IteratorAggregate`](http://php.net/manual/en/class.iteratoraggregate.php) that wraps around
a DB transaction and calls 
[`ObjectManager#flush()`](https://github.com/doctrine/common/blob/v2.5.1/lib/Doctrine/Common/Persistence/ObjectManager.php#L120)
and [`ObjectManager#clear()`](https://github.com/doctrine/common/blob/v2.5.1/lib/Doctrine/Common/Persistence/ObjectManager.php#L88)
on the given [`EntityManager`](https://github.com/doctrine/doctrine2/blob/v2.5.1/lib/Doctrine/ORM/EntityManagerInterface.php).

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

Please note that the `$record` inside the loop will always be "fresh", as
the iterator re-fetches it on its own: this prevents you from having to
manually call [`ObjectManager#find()`](https://github.com/doctrine/common/blob/v2.5.1/lib/Doctrine/Common/Persistence/ObjectManager.php#L42)
on your own for every iteration.
