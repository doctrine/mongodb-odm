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
    Documents\Image;

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
$image = new Image();
$image->setName('testing');
$image->setFile('/Users/jwage/Desktop/Photo_1.jpg');

$dm->persist($image);
$dm->flush();
*/

/*
$image = $dm->findByID('Documents\Image', '4bdc5a008ead0e6e4c010000');

$profile = new Profile();
$profile->setName('Jonathan H. Wage');
$profile->setImage($image);

$dm->persist($profile);
$dm->flush();

print_r($profile);
exit;
*/

$profile = $dm->createQuery('Documents\Profile')
    ->loadAssociation('image')
    ->where('id', '4bdc647b8ead0e2c4f010000')
    ->getSingleResult();

//$image->setFile('/Users/jwage/Desktop/test.png');
//$dm->flush();

$image = $profile->getImage();

header('Content-type: image/png;');
echo ($image->getFile()->getBytes());
exit;
//echo $image->getFile()->getBytes();

//print_r($image);

/*
$account = new Account();
$account->setName('Test Account');

$profile = new Profile();
$profile->setName('Jonathan H. Wage');

$user = new User();
$user->setProfile($profile);
$user->setAccount($account);
$user->setUsername('jwage');
$user->setPassword('test');
$user->addPhonenumber(new Phonenumber('6155139185'));
$user->addPhonenumber(new Phonenumber('5555555555'));

$address = new Address();
$address->setAddress('475 Buckhead Ave. Apt 2107');
$address->setCity('Atlanta');
$address->setState('Georgia');
$address->setZipcode('30303');

$user->addAddress($address);

$dm->persist($user);
$dm->flush();
$dm->clear();

$query = $dm->createQuery('Documents\User')
    ->loadAssociation('account')
    ->loadAssociation('profile')
    ->where('id', $user->getId());

$user = $query->getSingleResult();

print_r($user);
*/