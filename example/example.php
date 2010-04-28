<?php

require '/Users/jwage/Sites/doctrine2git/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader,
    Doctrine\ODM\MongoDB\EntityManager,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver,
    Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver,
    Entities\User,
    Entities\Address,
    Entities\Profile,
    Entities\Account,
    Entities\Phonenumber;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', '/Users/jwage/Sites/doctrine2git/lib');
$classLoader->register();

$classLoader = new ClassLoader('Symfony', '/Users/jwage/Sites/doctrine2git/lib/vendor');
$classLoader->register();

$classLoader = new ClassLoader('Entities', __DIR__);
$classLoader->register();

$config = new Configuration();
$config->setMetadataDriverImpl(new XmlDriver(__DIR__ . '/xml'));
//$config->setMetadataDriverImpl(new YamlDriver(__DIR__ . '/yaml'));

$em = EntityManager::create(new Mongo(), $config);

$account = new Account();
$account->setName('Test Account');

$user = new User();
$user->setAccount($account);
$user->setUsername('jwage');
$user->setPassword('jwage');
$user->addPhonenumber(new Phonenumber('6155139185'));

$em->persist($user);
$em->flush();

$query = $em->createQuery('Entities\User')
    ->loadAssociation('account')
    ->loadAssociation('phonenumbers')
    ->where('id', $user->getId());

$user = $query->getSingleResult();

print_r($user);