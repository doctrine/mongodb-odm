<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Event\DocumentNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Documents\Address;
use Documents\Profile;
use Documents\ProfileNotify;
use Documents\Phonenumber;
use Documents\Account;
use Documents\Group;
use Documents\User;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;

class ReferencesTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testManyDeleteReference()
    {
        $user = new \Documents\User();

        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();

        $this->dm->remove($user2);
        $this->dm->flush();

        $qb = $this->dm->createQueryBuilder('Documents\Group');
        $query = $qb->getQuery();
        $groups = $query->execute();

        $count = $groups->count();

        $this->assertEquals(0, $count);
    }

    public function testLazyLoadReference()
    {
        $user = new User();
        $profile = new Profile();
        $profile->setFirstName('Jonathan');
        $profile->setLastName('Wage');
        $user->setProfile($profile);
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();

        $user = $query->getSingleResult();

        $profile = $user->getProfile();

        $this->assertTrue($profile instanceof \Proxies\__CG__\Documents\Profile);

        $profile->getFirstName();

        $this->assertEquals('Jonathan', $profile->getFirstName());
        $this->assertEquals('Wage', $profile->getLastName());
    }

    public function testLazyLoadedWithNotifyPropertyChanged()
    {
        $user = new User();
        $profile = new ProfileNotify();
        $profile->setFirstName('Maciej');
        $user->setProfileNotify($profile);
        $user->setUsername('malarzm');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(get_class($user), $user->getId());
        $this->assertTrue($user->getProfileNotify() instanceof \Doctrine\Common\Persistence\Proxy);
        $this->assertFalse($user->getProfileNotify()->__isInitialized());

        $user->getProfileNotify()->setLastName('Malarz');
        $this->dm->flush();
        $this->dm->clear();

        $profile = $this->dm->find(get_class($profile), $profile->getProfileId());
        $this->assertEquals('Maciej', $profile->getFirstName());
        $this->assertEquals('Malarz', $profile->getLastName());
    }

    public function testOneEmbedded()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');

        $user = new User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $user->setAddress($address);

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $this->assertEquals($user->getAddress(), $user2->getAddress());
    }

    public function testManyEmbedded()
    {
        $user = new \Documents\User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $this->assertEquals($user->getPhonenumbers()->toArray(), $user2->getPhonenumbers()->toArray());
    }

    public function testOneReference()
    {
        $account = new Account();
        $account->setName('Test Account');

        $user = new User();
        $user->setUsername('jwage');
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->flush();
        $this->dm->clear();

        $accountId = $user->getAccount()->getId();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
    }

    public function testManyReference()
    {
        $user = new \Documents\User();
        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getGroups();

        $this->assertTrue($groups instanceof PersistentCollection);
        $this->assertTrue($groups[0]->getId() !== '');
        $this->assertTrue($groups[1]->getId() !== '');
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $groups = $user2->getGroups();
        $this->assertFalse($groups->isInitialized());

        $groups->count();
        $this->assertFalse($groups->isInitialized());

        $groups->isEmpty();
        $this->assertFalse($groups->isInitialized());

        $groups = $user2->getGroups();

        $this->assertTrue($groups instanceof PersistentCollection);
        $this->assertTrue($groups[0] instanceof Group);
        $this->assertTrue($groups[1] instanceof Group);

        $this->assertTrue($groups->isInitialized());

        unset($groups[0]);
        $groups[1]->setName('test');

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user3 = $query->getSingleResult();
        $groups = $user3->getGroups();

        $this->assertEquals('test', $groups[0]->getName());
        $this->assertEquals(1, count($groups));
    }
    
    public function testFlushInitializesEmptyPersistentCollection()
    {
        $user = new User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository('Documents\User')->find($user->getId());

        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->getGroups()->isInitialized(), 'A flushed collection should be initialized');
        $this->assertCount(2, $user->getGroups());
        $this->assertCount(2, $user->getGroups()->toArray());
    }

    public function testFlushInitializesNotEmptyPersistentCollection()
    {
        $user = new User();
        $user->addGroup(new Group('Group'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
        
        $user = $this->dm->getRepository('Documents\User')->find($user->getId());

        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertTrue($user->getGroups()->isInitialized(), 'A flushed collection should be initialized');
        $this->assertCount(3, $user->getGroups());
        $this->assertCount(3, $user->getGroups()->toArray());
    }

    public function testManyReferenceWithAddToSetStrategy()
    {
        $user = new \Documents\User();
        $user->addUniqueGroup($group1 = new Group('Group 1'));
        $user->addUniqueGroup($group1);
        $user->addUniqueGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getUniqueGroups();
        $this->assertEquals(3, count($groups));

        $this->assertTrue($groups instanceof PersistentCollection);
        $this->assertTrue($groups[0]->getId() !== '');
        $this->assertTrue($groups[1]->getId() !== '');
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        $groups = $user2->getUniqueGroups();
        $this->assertFalse($groups->isInitialized());

        $groups->count();
        $this->assertFalse($groups->isInitialized());

        $groups->isEmpty();
        $this->assertFalse($groups->isInitialized());

        $this->assertEquals(2, count($groups));

        $this->assertTrue($groups instanceof PersistentCollection);
        $this->assertTrue($groups[0] instanceof Group);
        $this->assertTrue($groups[1] instanceof Group);

        $this->assertTrue($groups->isInitialized());

        unset($groups[0]);
        $groups[1]->setName('test');

        $this->dm->flush();
        $this->dm->clear();

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user3 = $query->getSingleResult();
        $groups = $user3->getUniqueGroups();

        $this->assertEquals('test', $groups[0]->getName());
        $this->assertEquals(1, count($groups));
    }

    public function testSortReferenceManyOwningSide()
    {
        $user = new \Documents\User();
        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(get_class($user), $user->getId());

        $groups = $user->getSortedAscGroups();
        $this->assertEquals(2, $groups->count());
        $this->assertEquals('Group 1', $groups[0]->getName());
        $this->assertEquals('Group 2', $groups[1]->getName());

        $groups[1]->setName('Group 2a');

        $groups = $user->getSortedDescGroups();
        $this->assertEquals(2, $groups->count());
        $this->assertEquals('Group 2a', $groups[0]->getName());
        $this->assertEquals('Group 1', $groups[1]->getName());
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\DocumentNotFoundException
     * @expectedExceptionMessage The "Proxies\__CG__\Doctrine\ODM\MongoDB\Tests\Functional\DocumentWithArrayId" document with identifier {"identifier":2} could not be found.
     */
    public function testDocumentNotFoundExceptionWithArrayId()
    {
        $test = new DocumentWithArrayReference();
        $test->referenceOne = new DocumentWithArrayId();
        $test->referenceOne->id = array('identifier' => 1);

        $this->dm->persist($test);
        $this->dm->persist($test->referenceOne);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(get_class($test));

        $collection->update(
            array('_id' => new \MongoId($test->id)),
            array('$set' => array(
                'referenceOne.$id' => array('identifier' => 2),
            ))
        );

        $test = $this->dm->find(get_class($test), $test->id);
        $test->referenceOne->__load();
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\DocumentNotFoundException
     * @expectedExceptionMessage The "Proxies\__CG__\Documents\Profile" document with identifier "abcdefabcdefabcdefabcdef" could not be found.
     */
    public function testDocumentNotFoundExceptionWithMongoId()
    {
        $profile = new Profile();
        $user = new User();
        $user->setProfile($profile);

        $this->dm->persist($profile);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(get_class($user));

        $invalidId = new \MongoId('abcdefabcdefabcdefabcdef');

        $collection->update(
            array('_id' => new \MongoId($user->getId())),
            array('$set' => array(
                'profile.$id' => $invalidId,
            ))
        );

        $user = $this->dm->find(get_class($user), $user->getId());
        $profile = $user->getProfile();
        $profile->__load();
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\DocumentNotFoundException
     * @expectedExceptionMessage The "Proxies\__CG__\Doctrine\ODM\MongoDB\Tests\Functional\DocumentWithMongoBinDataId" document with identifier "testbindata" could not be found.
     */
    public function testDocumentNotFoundExceptionWithMongoBinDataId()
    {
        $test = new DocumentWithMongoBinDataReference();
        $test->referenceOne = new DocumentWithMongoBinDataId();
        $test->referenceOne->id = 'test';

        $this->dm->persist($test);
        $this->dm->persist($test->referenceOne);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(get_class($test));

        $invalidBinData = new \MongoBinData('testbindata', \MongoBinData::BYTE_ARRAY);

        $collection->update(
            array('_id' => new \MongoId($test->id)),
            array('$set' => array(
                'referenceOne.$id' => $invalidBinData,
            ))
        );

        $test = $this->dm->find(get_class($test), $test->id);
        $test->referenceOne->__load();
    }

    public function testDocumentNotFoundEvent()
    {
        $profile = new Profile();
        $user = new User();
        $user->setProfile($profile);

        $this->dm->persist($profile);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection(get_class($user));

        $invalidId = new \MongoId('abcdefabcdefabcdefabcdef');

        $collection->update(
            array('_id' => new \MongoId($user->getId())),
            array('$set' => array(
                'profile.$id' => $invalidId,
            ))
        );

        $user = $this->dm->find(get_class($user), $user->getId());
        $profile = $user->getProfile();

        $closure = function (DocumentNotFoundEventArgs $eventArgs) use ($profile) {
            $this->assertFalse($eventArgs->isExceptionDisabled());
            $this->assertSame($profile, $eventArgs->getObject());
            $eventArgs->disableException();
        };

        $this->dm->getEventManager()->addEventListener(Events::documentNotFound, new DocumentNotFoundListener($closure));

        $profile->__load();
    }
}

/** @ODM\Document */
class DocumentWithArrayReference
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="DocumentWithArrayId") */
    public $referenceOne;
}

/** @ODM\Document */
class DocumentWithArrayId
{
    /** @ODM\Id(strategy="none", options={"type"="hash"}) */
    public $id;
}


/** @ODM\Document */
class DocumentWithMongoBinDataReference
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="DocumentWithMongoBinDataId") */
    public $referenceOne;
}

/** @ODM\Document */
class DocumentWithMongoBinDataId
{
    /** @ODM\Id(strategy="none", options={"type"="bin"}) */
    public $id;
}

class DocumentNotFoundListener
{
    private $closure;

    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function documentNotFound(DocumentNotFoundEventArgs $eventArgs)
    {
        $closure = $this->closure;
        $closure($eventArgs);
    }
}
