<?php

require '/Users/jwage/Sites/doctrine2git/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver,
    Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver,
    Doctrine\ODM\MongoDB\Mapping\Driver\PHPDriver,
    Documents\User,
    Documents\Address,
    Documents\Profile,
    Documents\Account,
    Documents\Phonenumber,
    Documents\Image,
    Documents\Admin,
    Documents\Comment,
    Documents\MyComment,
    Documents\Page,
    Documents\BlogPost,
    Documents\Song,
    Documents\Configuration as ConfigurationDoc;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', '/Users/jwage/Sites/doctrine2git/lib');
$classLoader->register();

$classLoader = new ClassLoader('Symfony', '/Users/jwage/Sites/doctrine2git/lib/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$config = new Configuration();

$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$reader = new AnnotationReader();
$reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\Driver\\');
$config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

//$config->setMetadataDriverImpl(new XmlDriver(__DIR__ . '/xml'));
//$config->setMetadataDriverImpl(new YamlDriver(__DIR__ . '/yaml'));
//$config->setMetadataDriverImpl(new PHPDriver());

$dm = DocumentManager::create(new Mongo(), $config);

$profile = new Profile();
$profile->setName('Jonathan H. Wage');
$profile->addSong(new Song('Testinfuckckcg'));

$user = new User();
$user->setUsername('jwage');
$user->setPassword('changeme');
$user->addPhonenumber(new Phonenumber('6155139185'));
$user->setProfile($profile);

$configuration = new ConfigurationDoc();
$configuration->setTimezone('Eastern');
$configuration->setTheme('doctrine');

$user->setConfiguration($configuration);

$dm->persist($user);

$dm->flush();