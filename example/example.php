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
    Documents\Phonenumber;

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

$em = DocumentManager::create(new Mongo(), $config);

$account = new Account();
$account->setName('Test Account');

$profile = new Profile();
$profile->setName('Jonathan H. Wage');

$user = new User();
$user->setProfile($profile);
$user->setAccount($account);
$user->setUsername('jwage');
$user->setPassword('jwage');
$user->addPhonenumber(new Phonenumber('6155139185'));

$address = new Address();
$address->setAddress('475 Buckhead Ave. Apt 2107');
$address->setCity('Atlanta');
$address->setState('Georgia');
$address->setZipcode('30303');

$user->addAddress($address);

$em->persist($user);
$em->flush();

$query = $em->createQuery('Documents\User')
    ->loadAssociation('account')
    ->loadAssociation('phonenumbers')
    ->where('id', $user->getId());

$user = $query->getSingleResult();

print_r($user);