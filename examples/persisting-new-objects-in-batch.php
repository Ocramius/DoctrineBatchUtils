<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use DoctrineBatchUtils\BatchProcessing\SimpleBatchIteratorAggregate;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * @return EntityManager
 */
$configureOrm = function () {
    AnnotationRegistry::registerLoader('class_exists');
    $configuration = new Configuration();

    $configuration->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), [__DIR__]));
    $configuration->setAutoGenerateProxyClasses(\Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_EVAL);
    $configuration->setProxyNamespace('ORMProxies');
    $configuration->setProxyDir(sys_get_temp_dir());

    $entityManager = EntityManager::create(
        [
            'driverClass' => \Doctrine\DBAL\Driver\PDOSqlite\Driver::class,
            'memory'      => true,
        ],
        $configuration
    );

    (new SchemaTool($entityManager))
        ->createSchema(
            $entityManager
                ->getMetadataFactory()
                ->getAllMetadata()
        );

    return $entityManager;
};

/** @ORM\Entity */
class MyEntity
{
    /** @ORM\Id @ORM\GeneratedValue(strategy="NONE") @ORM\Column(type="integer") */
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}

$entityManager = $configureOrm();

// Bootstrapping the ORM
/** @var $iterable int[] */
$iterable = SimpleBatchIteratorAggregate::fromTraversableResult(
    call_user_func(function () use ($entityManager) {
        for ($i = 0; $i < 100000; $i += 1) {
            $entityManager->persist(new MyEntity($i));

            yield $i;
        }
    }),
    $entityManager,
    100 // flush/clear after 100 iterations
);

foreach ($iterable as $record) {
    // operate on records here

    var_dump([MyEntity::class => $record]);
    var_dump(['memory_get_peak_usage()' => (memory_get_peak_usage(true) / 1024 / 1024) . ' MiB']);
}
