<?php

require_once 'PHPUnit/Framework.php';
require_once '/Users/jwage/Sites/doctrine2git/lib/Doctrine/Common/ClassLoader.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mongo;

$classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', '/Users/jwage/Sites/doctrine2git/lib');
$classLoader->register();

class FunctionalTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dm = DocumentManager::create(new Mongo());
    }

    public function tearDown()
    {
        $documents = array('User', 'Account', 'Profile', 'Address', 'Group', 'Phonenumber');
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document)->drop();
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
        
        $this->dm->persist($user);
        $this->dm->flush();

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
        $query = $this->dm->createQuery('User')
            ->where('id', $user->id);

        $user = $query->getSingleResult();
        $this->assertSame($user, $user);

        $this->dm->clear();

        $user2 = $query->getSingleResult();
        $this->assertNotSame($user, $user2);
    }

    public function testLazyLoadAssociation()
    {
        $user = $this->_createTestUser();

        $query = $this->dm->createQuery('User')
            ->where('id', $user->id)
            ->refresh();
        $user = $query->getSingleResult();

        $this->assertEquals('profiles', $user->profile['$ref']);
        $this->assertTrue($user->profile['$id'] instanceof \MongoId);

        $this->dm->loadDocumentAssociation($user, 'profile');
        $this->assertEquals('Jonathan', $user->profile->firstName);
        $this->assertEquals('Wage', $user->profile->lastName);
    }

    public function testLoadAssociationInQuery()
    {
        $user = $this->_createTestUser();
        $query = $this->dm->createQuery('User')
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

        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQuery('User')
            ->where('id', $user->id)
            ->getSingleResult();
        $this->assertEquals($user->address, $user2->address);
    }

    public function testManyEmbeddedAssociation()
    {
        $user = $this->_createTestUser();
        $user->phonenumbers[] = new Phonenumber('6155139185');
        $user->phonenumbers[] = new Phonenumber('6153303769');
        
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQuery('User')
            ->where('id', $user->id)
            ->getSingleResult();

        $this->assertEquals($user->phonenumbers, $user2->phonenumbers);
    }

    public function testOneAssociation()
    {
        $user = $this->_createTestUser();

        $user->account = new Account();
        $user->account->name = 'Test Account';

        $this->dm->flush();
        $this->dm->clear();

        $accountId = $user->account->id;

        $user2 = $this->dm->createQuery('User')
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

        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue(isset($user->groups[0]->id));
        $this->assertTrue(isset($user->groups[1]->id));

        $user2 = $this->dm->createQuery('User')
            ->where('id', $user->id)
            ->refresh()
            ->getSingleResult();
 
        $this->assertTrue($user2->groups[0]['$id'] instanceof \MongoId);
        $this->assertTrue($user2->groups[1]['$id'] instanceof \MongoId);

        $this->dm->loadDocumentAssociation($user2, 'groups');

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

        $this->dm->persist($user);
        $this->dm->flush();

        $user->account->name = 'w00t';
        $this->dm->flush();

        $this->dm->refresh($user);
        $this->dm->loadDocumentAssociation($user, 'account');
        
        $this->assertEquals('w00t', $user->account->name);

        $this->dm->remove($user);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testDetach()
    {
        $user = new User();
        $user->username = 'jon';
        $user->password = 'changeme';
        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = 'whoop';
        $this->dm->detach($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->findByID('User', $user->id);
        $this->assertEquals('jon', $user2->username);
    }

    public function testAliases()
    {
        $user = new User();
        $user->aliasTest = 'w00t';
        $this->dm->persist($user);
        $this->dm->flush();

        $user2 = $this->dm->getDocumentCollection('User')->findOne(array('_id' => new MongoId($user->id)));
        $this->assertEquals('w00t', $user2[0]);

        $user->aliasTest = 'ok';
        $this->dm->flush();

        $user2 = $this->dm->getDocumentCollection('User')->findOne(array('_id' => new MongoId($user->id)));
        $this->assertEquals('ok', $user2[0]);

        $user = $this->dm->createQuery('User')
            ->where('aliasTest', 'ok')
            ->getSingleResult();

        $this->assertTrue($user instanceof User);
    }
}

class User
{
    public $id;
    public $username;
    public $password;
    public $address;
    public $profile;
    public $phonenumbers;
    public $groups;
    public $account;
    public $aliasTest;

    public function __construct()
    {
        $this->phonenumbers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public static function loadMetadata(ClassMetadata $class)
    {
        $class->setDB('doctrine_odm_tests');
        $class->setCollection('users');

        $class->mapField(array(
            'name' => 0,
            'fieldName' => 'aliasTest'
        ));

        $class->mapOneEmbedded(array(
            'fieldName' => 'address',
            'targetDocument' => 'Address'
        ));
        $class->mapManyEmbedded(array(
            'fieldName' => 'phonenumbers', 
            'targetDocument' => 'Phonenumber'
        ));
        $class->mapOneAssociation(array(
            'fieldName' => 'profile',
            'targetDocument' => 'Profile',
            'cascadeDelete' => true
        ));
        $class->mapOneAssociation(array(
            'fieldName' => 'account',
            'targetDocument' => 'Account',
            'cascadeDelete' => true
        ));
        $class->mapManyAssociation(array(
            'fieldName' => 'groups',
            'targetDocument' => 'Group'
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