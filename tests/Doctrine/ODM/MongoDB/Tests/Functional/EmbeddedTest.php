<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Documents\Address;
use Documents\Phonenumber;
use Documents\Account;
use Documents\User;
use Documents\Functional\EmbeddedTestLevel0;
use Documents\Functional\EmbeddedTestLevel0b;
use Documents\Functional\EmbeddedTestLevel1;
use Documents\Functional\EmbeddedTestLevel2;
use Documents\Functional\NotSaved;
use Documents\Functional\NotSavedEmbedded;
use Documents\Functional\VirtualHost;
use Documents\Functional\VirtualHostDirective;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class EmbeddedTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSetEmbeddedToNull()
    {
        $user = new User();
        $user->setId((string) new \MongoId());
        $user->setUsername('jwage');
        $user->setAddress(null);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
        $userId = $user->getId();

        $user = $this->dm->getRepository('Documents\User')->find($userId);
        $this->assertEquals($userId, $user->getId());
        $this->assertNull($user->getAddress());
    }

    public function testFlushEmbedded()
    {
        $test = new EmbeddedTestLevel0();
        $test->name = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository('Documents\Functional\EmbeddedTestLevel0')->findOneBy(array('name' => 'test'));
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel0', $test);

        // Adding this flush here makes level1 not to be inserted.
        $this->dm->flush();

        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find('Documents\Functional\EmbeddedTestLevel0', $test->id);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel0', $test);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel1', $test->level1[0]);

        $test->level1[0]->name = 'changed';
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'testing';
        $test->level1->add($level1);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find('Documents\Functional\EmbeddedTestLevel0', $test->id);
        $this->assertEquals(2, count($test->level1));
        $this->assertEquals('changed', $test->level1[0]->name);
        $this->assertEquals('testing', $test->level1[1]->name);

        unset($test->level1[0]);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertEquals(1, count($test->level1));
    }

    public function testOneEmbedded()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');

        $addressClone = clone $address;

        $user = new User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $user->setAddress($address);

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId())
            ->getQuery()
            ->getSingleResult();
        $this->assertNotNull($user);
        $this->assertEquals($addressClone, $user->getAddress());

        $oldAddress = $user->getAddress();
        $address = new Address();
        $address->setAddress('Someplace else');
        $user->setAddress($address);
        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($user);
        $this->assertNotEmpty($changeSet['address']);
        $this->assertSame($oldAddress, $changeSet['address'][0]);
        $this->assertSame($user->getAddress(), $changeSet['address'][1]);
    }

    public function testRemoveOneEmbedded()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');

        $user = new User();
        $user->setUsername('jwage');
        $user->setAddress($address);

        $this->dm->persist($user);
        $this->dm->flush();

        $user->removeAddress();
        $this->assertNull($user->getAddress());

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId())
            ->getQuery()
            ->getSingleResult();
        $this->assertNotNull($user);
        $this->assertNull($user->getAddress());
    }

    public function testManyEmbedded()
    {
        $user = new \Documents\User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQueryBuilder('Documents\User')
            ->field('id')->equals($user->getId())
            ->getQuery()
            ->getSingleResult();
        $this->assertNotNull($user2);
        $this->assertEquals($user->getPhonenumbers()->toArray(), $user2->getPhonenumbers()->toArray());
    }

    public function testPostRemoveEventOnEmbeddedManyDocument()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder(get_class($test))
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        $level1 = $test->level1[0];

        // $test->level1[0] is available
        $this->assertEquals('test level1 #1', $level1->name);

        // remove all level1 from test
        $test->level1->clear();
        $this->dm->flush();

        // verify that level1 lifecycle callbacks have been called
        $this->assertTrue($level1->preRemove, 'the removed embedded document executed the PreRemove lifecycle callback');
        $this->assertTrue($level1->postRemove, 'the removed embedded document executed the PostRemove lifecycle callback');
    }

    public function testRemoveEmbeddedManyDocument()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder(get_class($test))
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();

        // $test->level1[0] is available
        $this->assertEquals('test level1 #1', $test->level1[0]->name);

        // remove all level1 from test
        $test->level1->clear();
        $this->dm->flush();
        $this->dm->clear();

        // verify that test has no more level1
        $this->assertEquals(0, $test->level1->count());

        // retrieve test
        $test = $this->dm->createQueryBuilder(get_class($test))
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $test->level1);

        // verify that test has no more level1
        $this->assertEquals(0, $test->level1->count());
    }

    public function testRemoveDeepEmbeddedManyDocument()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->oneLevel1 = $level1;

        // embed one level2 in level1
        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder(get_class($test))
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        // $test->oneLevel1->level2[0] is available
        $this->assertEquals('test level2 #1', $level2->name);

        // remove all level2 from level1
        $level1->level2->clear();
        $this->dm->flush();
        $this->dm->clear();

        // verify that level1 has no more level2
        $this->assertEquals(0, $level1->level2->count());

        // retrieve test
        $test = $this->dm->createQueryBuilder(get_class($test))
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        $level1 = $test->oneLevel1;

        // verify that level1 has no more level2
        $this->assertEquals(0, $level1->level2->count());
    }

    public function testPostRemoveEventOnDeepEmbeddedManyDocument()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->oneLevel1 = $level1;

        // embed one level2 in level1
        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder(get_class($test))
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        // $test->oneLevel1->level2[0] is available
        $this->assertEquals('test level2 #1', $level2->name);

        // remove all level2 from level1
        $level1->level2->clear();
        $this->dm->flush();

        // verify that level2 lifecycle callbacks have been called
        $this->assertTrue($level2->preRemove, 'the removed embedded document executed the PreRemove lifecycle callback');
        $this->assertTrue($level2->postRemove, 'the removed embedded document executed the PostRemove lifecycle callback');
    }

    public function testEmbeddedLoadEvents()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->oneLevel1 = $level1;

        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder(get_class($test))
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        $this->assertTrue($level1->preLoad);
        $this->assertTrue($level1->postLoad);
        $this->assertTrue($level2->preLoad);
        $this->assertTrue($level2->postLoad);
    }

    public function testEmbeddedDocumentChangesParent()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $user = new User();
        $user->setUsername('jwagettt');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertNotNull($user);
        $address = $user->getAddress();
        $address->setAddress('changed');

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find('Documents\User', $user->getId());
        $this->assertEquals('changed', $user->getAddress()->getAddress());
    }

    public function testRemoveEmbeddedDocument()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $user = new User();
        $user->setUsername('jwagettt');
        $user->setAddress($address);
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6155139185'));

        $this->dm->persist($user);
        $this->dm->flush();

        $user->removeAddress();

        $user->getPhonenumbers()->remove(0);
        $user->getPhonenumbers()->remove(1);

        $this->dm->flush();

        $check = $this->dm->getDocumentCollection('Documents\User')->findOne();
        $this->assertEmpty($check['phonenumbers']);
        $this->assertFalse(isset($check['address']));
    }

    public function testRemoveAddDeepEmbedded()
    {
        $vhost = new VirtualHost();

        $directive1 = new VirtualHostDirective('DirectoryIndex', 'index.php');
        $vhost->getVHostDirective()->addDirective($directive1);

        $directive2 = new VirtualHostDirective('Directory', '/var/www/html');
        $directive2->addDirective(new VirtualHostDirective('AllowOverride','All'));
        $vhost->getVHostDirective()->addDirective($directive2);

        $directive3 = new VirtualHostDirective('Directory', '/var/www/html');
        $directive3->addDirective(new VirtualHostDirective('RewriteEngine','on'));
        $vhost->getVHostDirective()->addDirective($directive3);

        $this->dm->persist($vhost);
        $this->dm->flush();

        $vhost->getVHostDirective()->removeDirective($directive2);

        $directive4 = new VirtualHostDirective('Directory', '/var/www/html');
        $directive4->addDirective(new VirtualHostDirective('RewriteEngine','on'));
        $vhost->getVHostDirective()->addDirective($directive4);


        $this->dm->flush();
        $this->dm->clear();

        $vhost = $this->dm->find('Documents\Functional\VirtualHost', $vhost->getId());

        foreach($vhost->getVHostDirective()->getDirectives() as $directive)
        {
            $this->assertNotEmpty($directive->getName());
        }
    }

    public function testEmbeddedDocumentNotSavedFields()
    {
        $document = new NotSaved();
        $document->embedded = new NotSavedEmbedded();
        $document->embedded->name = 'foo';
        $document->embedded->notSaved = 'bar';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find('Documents\Functional\NotSaved', $document->id);

        $this->assertEquals('foo', $document->embedded->name);
        $this->assertNull($document->embedded->notSaved);
    }

    public function testChangeEmbedOneDocumentId()
    {
        $originalId = (string) new \MongoId();

        $test = new ChangeEmbeddedIdTest();
        $test->embed = new EmbeddedDocumentWithId();
        $test->embed->id = $originalId;
        $this->dm->persist($test);

        $this->dm->flush();

        $newId = (string) new \MongoId();

        $test->embed->id = $newId;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(get_class($test), $test->id);

        $this->assertEquals($newId, $test->embed->id);
    }

    public function testChangeEmbedManyDocumentId()
    {
        $originalId = (string) new \MongoId();

        $test = new ChangeEmbeddedIdTest();
        $test->embedMany[] = new EmbeddedDocumentWithId();
        $test->embedMany[0]->id = $originalId;
        $this->dm->persist($test);

        $this->dm->flush();

        $newId = (string) new \MongoId();

        $test->embedMany[0]->id = $newId;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(get_class($test), $test->id);

        $this->assertEquals($newId, $test->embedMany[0]->id);
    }

    public function testEmbeddedDocumentsWithSameIdAreNotSameInstance()
    {
        $originalId = (string) new \MongoId();

        $test = new ChangeEmbeddedIdTest();
        $test->embed = new EmbeddedDocumentWithId();
        $test->embedMany[] = $test->embed;
        $test->embedMany[] = $test->embed;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(get_class($test), $test->id);

        $this->assertNotSame($test->embed, $test->embedMany[0]);
        $this->assertNotSame($test->embed, $test->embedMany[1]);
    }

    public function testWhenCopyingManyEmbedSubDocumentsFromOneDocumentToAnotherWillNotAffectTheSourceDocument()
    {
        $test1 = new ChangeEmbeddedIdTest();

        $embedded = new EmbeddedDocumentWithId();
        $embedded->id = (string) new \MongoId();;
        $test1->embedMany = array($embedded);

        $this->dm->persist($test1);
        $this->dm->flush();

        $test2 = new ChangeEmbeddedIdTest();
        $test2->embedMany = $test1->embedMany; //using clone will work
        $this->dm->persist($test2);

        $this->assertNotSame($test1->embedMany->first(), $test2->embedMany->first());

        $this->dm->flush();


        //do some operations on test1
        $this->dm->persist($test1);
        $this->dm->flush();

        $this->dm->clear(); //get clean results from mongo
        $test1 = $this->dm->find(get_class($test1), $test1->id);

        $this->assertEquals(1, count($test1->embedMany));
    }

    public function testReusedEmbeddedDocumentsAreClonedInFact()
    {
        $test1 = new ChangeEmbeddedIdTest();
        $test2 = new ChangeEmbeddedIdTest();

        $embedded = new EmbeddedDocumentWithId();
        $embedded->id = (string) new \MongoId();

        $test1->embed = $embedded;
        $test2->embed = $embedded;

        $this->dm->persist($test1);
        $this->dm->persist($test2);

        $this->dm->flush();

        $this->assertNotSame($test1->embed, $test2->embed);

        $originalTest1 = $this->uow->getOriginalDocumentData($test1);
        $this->assertSame($originalTest1['embed'], $test1->embed);
        $originalTest2 = $this->uow->getOriginalDocumentData($test2);
        $this->assertSame($originalTest2['embed'], $test2->embed);
    }

    public function testEmbeddedDocumentWithDifferentFieldNameAnnotation()
    {
        $test1 = new ChangeEmbeddedWithNameAnnotationTest();

        $embedded = new EmbeddedDocumentWithId();
        $embedded->id = (string) new \MongoId();

        $firstEmbedded = new EmbedDocumentWithAnotherEmbed();
        $firstEmbedded->embed = $embedded;

        $secondEmbedded = clone $firstEmbedded;

        $test1->embedOne = $firstEmbedded;
        $test1->embedTwo = $secondEmbedded;

        $this->dm->persist($test1);
    }
}

/**
 * @ODM\Document
 */
class ChangeEmbeddedIdTest
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument="EmbeddedDocumentWithId")
     */
    public $embed;

    /**
     * @ODM\EmbedMany(targetDocument="EmbeddedDocumentWithId")
     */
    public $embedMany;

    public function __construct()
    {
        $this->embedMany = new ArrayCollection();
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedDocumentWithId
{
    /** @ODM\Id */
    public $id;
}

/**
 * @ODM\Document
 */
class ChangeEmbeddedWithNameAnnotationTest
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument="EmbeddedDocumentWithAnotherEmbedded")
     */
    public $embedOne;

    /**
     * @ODM\EmbedOne(targetDocument="EmbeddedDocumentWithAnotherEmbedded")
     */
    public $embedTwo;
}

/**
 * @ODM\EmbeddedDocument
 */
class EmbedDocumentWithAnotherEmbed
{
    /**
     * @ODM\EmbedOne(targetDocument="EmbeddedDocumentWithId", name="m_id")
     */
    public $embed;
}
