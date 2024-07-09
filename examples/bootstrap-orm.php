<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ORMSetup;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/entity/MyEntity.php';


/**
 * @return EntityManager
 */
return function () {
    $configuration = new Configuration();

    $configuration->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/entity']));
    $configuration->setAutoGenerateProxyClasses(\Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_EVAL);
    $configuration->setProxyNamespace('ORMProxies');
    $configuration->setProxyDir(sys_get_temp_dir());

    $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);
    $entityManager = new EntityManager($connection, $configuration);

    (new SchemaTool($entityManager))
        ->createSchema(
            $entityManager
                ->getMetadataFactory()
                ->getAllMetadata()
        );

    return $entityManager;
};
