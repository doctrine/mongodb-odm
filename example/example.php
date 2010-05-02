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
    Documents\BlogPost;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', '/Users/jwage/Sites/doctrine2git/lib');
$classLoader->register();

$classLoader = new ClassLoader('Symfony', '/Users/jwage/Sites/doctrine2git/lib/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Documents', __DIR__);
$classLoader->register();

$config = new Configuration();

$reader = new AnnotationReader();
$reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\Driver\\');
$config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

//$config->setMetadataDriverImpl(new XmlDriver(__DIR__ . '/xml'));
//$config->setMetadataDriverImpl(new YamlDriver(__DIR__ . '/yaml'));
//$config->setMetadataDriverImpl(new PHPDriver());

$dm = DocumentManager::create(new Mongo(), $config);

/*
$user = new Admin();
$user->setUsername('jwage');
$dm->persist($user);
$dm->flush();

print_r($user);

$blogPost = new BlogPost();
$blogPost->setTeaser('test');

$dm->persist($blogPost);
$dm->flush();

print_r($blogPost);
*/

$blogPost = $dm->findByID('Documents\Page', '4bdcfe408ead0e3978010000');
print_r($blogPost);