<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;

$entityManager = call_user_func(require __DIR__ . '/bootstrap-orm.php');
assert($entityManager instanceof EntityManager);

// Bootstrapping the ORM
/** @var int[] $iterable */
$iterable = SimpleBatchIteratorAggregate::fromTraversableResult(
    call_user_func(static function () use ($entityManager) {
        for ($i = 0; $i < 10000; $i += 1) {
            $entityManager->persist(new MyEntity($i));

            yield $i;
        }
    }),
    $entityManager,
    100, // flush/clear after 100 iterations
);

foreach ($iterable as $record) {
    // operate on records here

    var_dump([MyEntity::class => $record]);
    var_dump(['memory_get_peak_usage()' => (memory_get_peak_usage(true) / 1024 / 1024) . ' MiB']);
}
