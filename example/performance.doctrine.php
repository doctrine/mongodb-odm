<?php

require '/Users/jwage/Sites/doctrine2git/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', '/Users/jwage/Sites/doctrine2git/lib');
$classLoader->register();

$config = new Configuration();
$config->setMetadataCacheImpl(new ApcCache());
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$reader = new AnnotationReader();
$reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
$config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

$mongo = new Mongo();
$dm = DocumentManager::create($mongo, $config);

/** @Document(db="performance", collection="users") */
class User
{
    /** @Id */
    public $id;

    /** @String */
    public $username;

    /** @String */
    public $password;
}

$start = microtime(true);

/*
$user = new User();
$user->username = 'user';
$user->password = 'password';
$dm->persist($user);
$dm->flush();
*/

$user = $dm->findOne('User', array('username' => 'user'));

$end = microtime(true);
$total = $end - $start;

print_r($user);

echo "Doctrine MongoDB ODM: " . $total."\n";