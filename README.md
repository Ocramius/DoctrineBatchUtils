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
