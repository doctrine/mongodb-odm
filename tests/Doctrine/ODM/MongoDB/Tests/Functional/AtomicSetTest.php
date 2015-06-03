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
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());

        $user->surname = "Malarz";
        $user->phonenumbers[] = new Phonenumber('87654321');
        $this->ql->reset();
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertEquals('Malarz', $user->surname);
        $this->assertCount(2, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());
        $this->assertEquals('87654321', $user->phonenumbers[1]->getPhonenumber());
    }

    public function testAtomicUpsert()
    {
        $user = new AtomicUser('Maciej');
        $user->id = new \MongoId();
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());
    }

    public function testAtomicCollectionUnset()
    {
        $user = new AtomicUser('Maciej');
        $user->phonenumbers[] = new Phonenumber('12345678');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonenumbers);
        $this->assertEquals('12345678', $user->phonenumbers[0]->getPhonenumber());

        $user->surname = "Malarz";
        $user->phonenumbers = null;
        $this->ql->reset();
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertEquals('Malarz', $user->surname);
        $this->assertCount(0, $user->phonenumbers);
    }
    
    public function testAtomicSetArray()
    {
        $user = new AtomicUser('Maciej');
        $user->phonenumbersArray[1] = new Phonenumber('12345678');
        $user->phonenumbersArray[2] = new Phonenumber('87654321');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(2, $user->phonenumbersArray);
        $this->assertEquals('12345678', $user->phonenumbersArray[0]->getPhonenumber());
        $this->assertEquals('87654321', $user->phonenumbersArray[1]->getPhonenumber());

        unset($user->phonenumbersArray[0]);
        $this->ql->reset();
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $user->phonenumbersArray);
        $this->assertEquals('87654321', $user->phonenumbersArray[0]->getPhonenumber());
        $this->assertFalse(isset($user->phonenumbersArray[1]));
    }

    public function testAtomicCollectionWithAnotherNested()
    {
        $user = new AtomicUser('Maciej');
        $privateBook = new Phonebook('Private');
        $privateBook->addPhonenumber(new Phonenumber('12345678'));
        $user->phonebooks[] = $privateBook;
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(1, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(1, $privateBook->getPhonenumbers());
        $this->assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->addPhonenumber(new Phonenumber('87654321'));
        $publicBook = new Phonebook('Public');
        $publicBook->addPhonenumber(new Phonenumber('10203040'));
        $user->phonebooks[] = $publicBook;
        $this->ql->reset();
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertEquals('Maciej', $user->name);
        $this->assertCount(2, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(2, $privateBook->getPhonenumbers());
        $this->assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());
        $this->assertEquals('87654321', $privateBook->getPhonenumbers()->get(1)->getPhonenumber());
        $publicBook = $user->phonebooks[1];
        $this->assertCount(1, $publicBook->getPhonenumbers());
        $this->assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->getPhonenumbers()->clear();
        $this->ql->reset();
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        $this->dm->clear();

        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(2, $user->phonebooks);
        $privateBook = $user->phonebooks[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(0, $privateBook->getPhonenumbers());
        $publicBook = $user->phonebooks[1];
        $this->assertCount(1, $publicBook->getPhonenumbers());
        $this->assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());
    }
    
    public function testWeNeedToGoDeeper()
    {
        $user = new AtomicUser('Maciej');
        $user->inception[0] = new GonnaBeDeep('start');
        $user->inception[0]->one = new GonnaBeDeep('start.one');
        $user->inception[0]->one->many[] = new GonnaBeDeep('start.one.many.0');
        $user->inception[0]->one->many[] = new GonnaBeDeep('start.one.many.1');
        $user->inception[0]->one->one = new GonnaBeDeep('start.one.one');
        $user->inception[0]->one->one->many[] = new GonnaBeDeep('start.one.one.many.0');
        $user->inception[0]->one->one->many[0]->many[] = new GonnaBeDeep('start.one.one.many.0.many.0');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertEquals(1, $this->ql->count());
        
        $user = $this->dm->getRepository(get_class($user))->find($user->id);
        $this->assertCount(1, $user->inception);
        $this->assertEquals($user->inception[0]->value, 'start');
        $this->assertNotNull($user->inception[0]->one);
        $this->assertEquals($user->inception[0]->one->value, 'start.one');
        $this->assertCount(2, $user->inception[0]->one->many);
        $this->assertEquals($user->inception[0]->one->many[0]->value, 'start.one.many.0');
        $this->assertEquals($user->inception[0]->one->many[1]->value, 'start.one.many.1');
        $this->assertNotNull($user->inception[0]->one->one);
        $this->assertEquals($user->inception[0]->one->one->value, 'start.one.one');
        $this->assertCount(1, $user->inception[0]->one->one->many);
        $this->assertEquals($user->inception[0]->one->one->many[0]->value, 'start.one.one.many.0');
        $this->assertCount(1, $user->inception[0]->one->one->many[0]->many);
        $this->assertEquals($user->inception[0]->one->one->many[0]->many[0]->value, 'start.one.one.many.0.many.0');
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

    /** @ODM\Int @ODM\Version */
    public $version = 1;
    
    /** @ODM\String */
    public $surname;
    
    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="Documents\Phonenumber") */
    public $phonenumbers;
    
    /** @ODM\EmbedMany(strategy="atomicSetArray", targetDocument="Documents\Phonenumber") */
    public $phonenumbersArray;
    
    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="Documents\Phonebook") */
    public $phonebooks;
    
    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument="GonnaBeDeep") */
    public $inception;
    
    public function __construct($name)
    {
        $this->name = $name;
        $this->phonenumbers = new ArrayCollection();
        $this->phonenumbersArray = new ArrayCollection();
        $this->phonebooks = new ArrayCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GonnaBeDeep
{
    /** @ODM\String */
    public $value;
    
    /** @ODM\EmbedOne(targetDocument="GonnaBeDeep") */
    public $one;
    
    /** @ODM\EmbedMany(targetDocument="GonnaBeDeep") */
    public $many;
    
    public function __construct($value)
    {
        $this->value = $value;
        $this->many = new ArrayCollection();
    }
}
