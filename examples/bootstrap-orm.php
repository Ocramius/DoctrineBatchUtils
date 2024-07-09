<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/entity/MyEntity.php';


/**
 * @return EntityManager
 */
return function () {
    $configuration = new Configuration();

    $configuration->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), [__DIR__ . '/entity']));
    $configuration->setAutoGenerateProxyClasses(\Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_EVAL);
    $configuration->setProxyNamespace('ORMProxies');
    $configuration->setProxyDir(sys_get_temp_dir());

    $entityManager = EntityManager::create(
        [
            'driverClass' => \Doctrine\DBAL\Driver\PDO\SQLite\Driver::class,
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
