<?php

require_once 'PHPUnit/Framework.php';
require_once '/Users/jwage/Sites/doctrine2git/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader,
    Doctrine\ODM\MongoDB\EntityManager,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

class FunctionalTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->em = new EntityManager(new \Mongo());
    }

    public function tearDown()
    {
        $entities = array('User', 'Account', 'Profile', 'Address', 'Group', 'Phonenumber');
        foreach ($entities as $entity) {
            $this->em->getEntityCollection($entity)->drop();
        }
    }

    private function _createTestUser()
    {
        $user = new User();
        $user->username = 'jwage';
        $user->password = 'changeme';
        $user->profile = new Profile();
        $user->profile->firstName = 'Jonathan';
        $user->profile->lastName = 'Wage';
        
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testIdentifiersAreSet()
    {
        $user = $this->_createTestUser();
        
        $this->assertTrue(isset($user->id));
        $this->assertTrue(isset($user->profile->id));
    }

    public function testIdentityMap()
    {
        $user = $this->_createTestUser();
        $query = $this->em->createQuery('User')
            ->where('id', $user->id);

        $user = $query->getSingleResult();
        $this->assertSame($user, $user);

        $this->em->clear();

        $user2 = $query->getSingleResult();
        $this->assertNotSame($user, $user2);
    }

    public function testLazyLoadAssociation()
    {
        $user = $this->_createTestUser();

        $query = $this->em->createQuery('User')
            ->where('id', $user->id);
        $user = $query->getSingleResult();

        $this->assertEquals('profiles', $user->profile['$ref']);
        $this->assertTrue($user->profile['$id'] instanceof \MongoId);

        $this->em->loadEntityAssociation($user, 'profile');
        $this->assertEquals('Jonathan', $user->profile->firstName);
        $this->assertEquals('Wage', $user->profile->lastName);
    }

    public function testLoadAssociationInQuery()
    {
        $user = $this->_createTestUser();
        $query = $this->em->createQuery('User')
            ->where('id', $user->id)
            ->loadAssociation('profile');
        $user4 = $query->getSingleResult();

        $this->assertEquals('Jonathan', $user4->profile->firstName);
        $this->assertEquals('Wage', $user4->profile->lastName);
    }

    public function testOneEmbeddedAssociation()
    {
        $user = $this->_createTestUser();
        $user->address = new Address();
        $user->address->address = '6512 Mercomatic Ct.';
        $user->address->city = 'Nashville';
        $user->address->state = 'TN';
        $user->address->zipcode = '37209';

        $this->em->flush();
        $this->em->clear();

        $user2 = $this->em->createQuery('User')
            ->where('id', $user->id)
            ->getSingleResult();
        $this->assertEquals($user->address, $user2->address);
    }

    public function testManyEmbeddedAssociation()
    {
        $user = $this->_createTestUser();
        $user->phonenumbers[] = new Phonenumber('6155139185');
        $user->phonenumbers[] = new Phonenumber('6153303769');
        
        $this->em->flush();
        $this->em->clear();

        $user2 = $this->em->createQuery('User')
            ->where('id', $user->id)
            ->getSingleResult();
        $this->assertEquals($user->phonenumbers, $user2->phonenumbers);
    }

    public function testOneAssociation()
    {
        $user = $this->_createTestUser();

        $user->account = new Account();
        $user->account->name = 'Test Account';

        $this->em->flush();
        $this->em->clear();

        $accountId = $user->account->id;

        $user2 = $this->em->createQuery('User')
            ->where('id', $user->id)
            ->loadAssociation('account')
            ->getSingleResult();

        $this->assertEquals($user->account, $user2->account);
        $this->assertEquals($accountId, $user2->account->id);
    }

    public function testManyAssociation()
    {
        $user = $this->_createTestUser();

        $user->groups[] = new Group('Group 1');
        $user->groups[] = new Group('Group 2');

        $this->em->flush();
        $this->em->clear();

        $this->assertTrue(isset($user->groups[0]->id));
        $this->assertTrue(isset($user->groups[1]->id));

        $user2 = $this->em->createQuery('User')
            ->where('id', $user->id)
            ->getSingleResult();
    
        $this->assertTrue($user2->groups[0]['$id'] instanceof \MongoId);
        $this->assertTrue($user2->groups[1]['$id'] instanceof \MongoId);

        $this->em->loadEntityAssociation($user2, 'groups');

        $this->assertTrue($user2->groups[0] instanceof Group);
        $this->assertTrue($user2->groups[1] instanceof Group);
    }

    public function testCascadeInsertUpdateAndRemove()
    {
        $user = new User();
        $user->username = 'jon';
        $user->password = 'changeme';
        $user->account = new Account();
        $user->account->name = 'Jon Test Account';

        $this->em->persist($user);
        $this->em->flush();

        $user->account->name = 'w00t';
        $this->em->flush();

        $this->em->refresh($user);
        $this->em->loadEntityAssociation($user, 'account');
        
        $this->assertEquals('w00t', $user->account->name);

        $this->em->remove($user);
        $this->em->flush();
        $this->em->clear();

        /*
        $user = $this->em->findByID('User', $user->id);
        print_r($user);
        exit;

        $account = $this->em->findByID('Account', $user->account->id);
        print_r($account);
        exit;
        $this->assertFalse((bool) $account);
        */
    }
}

class User
{
    public $id;
    public $username;
    public $password;
    public $address;
    public $profile;
    public $phonenumbers = array();
    public $groups = array();
    public $account;

    public static function loadMetadata(ClassMetadata $class)
    {
        $class->setDB('doctrine_odm_tests');
        $class->setCollection('users');

        $class->mapOneEmbedded(array(
            'fieldName' => 'address',
            'targetEntity' => 'Address'
        ));
        $class->mapManyEmbedded(array(
            'fieldName' => 'phonenumbers', 
            'targetEntity' => 'Phonenumber'
        ));
        $class->mapOneAssociation(array(
            'fieldName' => 'profile',
            'targetEntity' => 'Profile',
            'cascadeDelete' => true
        ));
        $class->mapOneAssociation(array(
            'fieldName' => 'account',
            'targetEntity' => 'Account',
            'cascadeDelete' => true
        ));
        $class->mapManyAssociation(array(
            'fieldName' => 'groups',
            'targetEntity' => 'Group'
        ));
    }
}

class Group
{
    public $id;
    public $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public static function loadMetadata(ClassMetadata $class)
    {
        $class->setDB('doctrine_odm_tests');
        $class->setCollection('groups');
    }
}

class Account
{
    public $id;
    public $name;

    public static function loadMetadata(ClassMetadata $class)
    {
        $class->setDB('doctrine_odm_tests');
        $class->setCollection('accounts');
    }
}

class Phonenumber
{
    public static function loadMetadata(ClassMetadata $class)
    {
        $class->setDB('doctrine_odm_tests');
        $class->setCollection('phonenumbers');
    }

    public $number;

    public function __construct($number)
    {
        $this->number = $number;
    }
}

class Address
{
    public $address;
    public $city;
    public $state;
    public $zipcode;
}

class Profile
{
    public $id;
    public $firstName;
    public $lastName;

    public static function loadMetadata(ClassMetadata $class)
    {
        $class->setDB('doctrine_odm_tests');
        $class->setCollection('profiles');
    }
}