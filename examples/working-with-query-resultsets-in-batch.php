<?php

use Doctrine\ORM\EntityManager;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;

/** @var $entityManager EntityManager */
$entityManager = call_user_func(require __DIR__ . '/bootstrap-orm.php');

// First, we persist a lot of data to work with. We do this in an iterator too to avoid killing our memory:
$persistAllEntries = SimpleBatchIteratorAggregate::fromTraversableResult(
    call_user_func(function () use ($entityManager) {
        for ($i = 0; $i < 10000; $i += 1) {
            $entityManager->persist(new MyEntity($i));

            yield $i;
        }
    }),
    $entityManager,
    100 // flush/clear after 100 iterations
);

\iterator_to_array($persistAllEntries); // quickly consume the previous iterator

/** @var $savedEntries MyEntity[] */
$savedEntries = SimpleBatchIteratorAggregate::fromQuery(
    $entityManager->createQuery(sprintf('SELECT e FROM %s e', MyEntity::class)),
    100 // flush/clear after 100 iterations
);

foreach ($savedEntries as $savedEntry) {
    // operate on records here

    var_dump([MyEntity::class => $savedEntry->id]);
    var_dump(['memory_get_peak_usage()' => (memory_get_peak_usage(true) / 1024 / 1024) . ' MiB']);
}
