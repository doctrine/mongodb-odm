<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Phonebook;
use Documents\Phonenumber;

/**
 * CollectionPersister will throw exception when collection with atomicSet
 * or atomicSetArray should be handled by it. If no exception was thrown it
 * means that collection update was handled by DocumentPersister.
 */
class AtomicSetTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testAtomicInsertAndUpdate()
    {
        $user = new AtomicUser('Maciej');
        $user->phonenumbers['home'] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $user->surname = "Malarz";
        $user->phonenumbers['work'] = new Phonenumber('87654321');
        $this->dm->flush();
        $this->dm->clear();
        $newUser = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $newUser->name);
        $this->assertEquals('Malarz', $newUser->surname);
        $this->assertCount(2, $newUser->phonenumbers);
        $this->assertNotNull($newUser->phonenumbers->get('home'));
        $this->assertNotNull($newUser->phonenumbers->get('work'));
    }
    
    public function testAtomicUpsert()
    {
        $user = new AtomicUser('Maciej');
        $user->id = new \MongoId();
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
        $newUser = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $newUser->name);
        $this->assertCount(1, $newUser->phonenumbers);
    }
    
    public function testAtomicCollectionUnset()
    {
        $user = new AtomicUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $user->surname = "Malarz";
        $user->phonenumbers = null;
        $this->dm->flush();
        $this->dm->clear();
        $newUser = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $newUser->name);
        $this->assertEquals('Malarz', $newUser->surname);
        $this->assertCount(0, $newUser->phonenumbers);
    }
    
    public function testAtomicSetArray()
    {
        $user = new AtomicUser('Maciej');
        $user->phonenumbersArray[] = new Phonenumber('12345678');
        $user->phonenumbersArray[] = new Phonenumber('87654321');
        $this->dm->persist($user);
        $this->dm->flush();
        unset($user->phonenumbersArray[0]);
        $this->dm->flush();
        $this->dm->clear();
        $newUser = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $newUser->phonenumbersArray);
        $this->assertFalse(isset($newUser->phonenumbersArray[1]));
    }
    
    public function testAtomicCollectionWithAnotherNested()
    {
        $user = new AtomicUser('Maciej');
        $private = new Phonebook('Private');
        $private->addPhonenumber(new Phonenumber('12345678'));
        $user->phonebooks['private'] = $private;
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $private = $user->phonebooks->get('private');
        $this->assertNotNull($private);
        $this->assertCount(1, $private->getPhonenumbers());
        $this->assertEquals('12345678', $private->getPhonenumbers()->get(0)->getPhonenumber());

        $private->addPhonenumber(new Phonenumber('87654321'));
        $public = new Phonebook('Public');
        $public->addPhonenumber(new Phonenumber('10203040'));
        $user->phonebooks['public'] = $public;
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $private = $user->phonebooks->get('private');
        $this->assertNotNull($private);
        $this->assertCount(2, $private->getPhonenumbers());
        $this->assertEquals('12345678', $private->getPhonenumbers()->get(0)->getPhonenumber());
        $this->assertEquals('87654321', $private->getPhonenumbers()->get(1)->getPhonenumber());
        $public = $user->phonebooks->get('public');
        $this->assertNotNull($public);
        $this->assertCount(1, $public->getPhonenumbers());
        $this->assertEquals('10203040', $public->getPhonenumbers()->get(0)->getPhonenumber());
    }
}

/**
 * @ODM\Document
 */
class AtomicUser
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\String */
    public $name;
    
    /** @ODM\String */
    public $surname;
    
    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="Documents\Phonenumber") */
    public $phonenumbers;
    
    /** @ODM\EmbedMany(strategy="atomicSetArray", targetDocument="Documents\Phonenumber") */
    public $phonenumbersArray;
    
    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="Documents\Phonebook") */
    public $phonebooks;
    
    public function __construct($name)
    {
        $this->name = $name;
        $this->phonenumbers = new ArrayCollection();
        $this->phonenumbersArray = new ArrayCollection();
        $this->phonebooks = new ArrayCollection();
    }
}
