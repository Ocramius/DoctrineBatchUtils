<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/entity/MyEntity.php';

/** @return EntityManager */
return static function () {
    $configuration = new Configuration();

    $configuration->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/entity']));
    $configuration->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
    $configuration->setProxyNamespace('ORMProxies');
    $configuration->setProxyDir(sys_get_temp_dir());

    $connection    = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);
    $entityManager = new EntityManager($connection, $configuration);

    (new SchemaTool($entityManager))
        ->createSchema(
            $entityManager
                ->getMetadataFactory()
                ->getAllMetadata(),
        );

    return $entityManager;
};
