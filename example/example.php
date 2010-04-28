<?php

require '/Users/jwage/Sites/doctrine2git/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader,
    Doctrine\ODM\MongoDB\EntityManager,
    Entities\User,
    Entities\Address,
    Entities\Profile,
    Entities\Account,
    Entities\Phonenumber;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Entities', __DIR__);
$classLoader->register();

$em = new EntityManager(new Mongo());

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