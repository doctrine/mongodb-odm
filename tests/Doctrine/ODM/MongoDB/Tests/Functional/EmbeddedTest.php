<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Address;
use Documents\Functional\EmbeddedTestLevel0;
use Documents\Functional\EmbeddedTestLevel0b;
use Documents\Functional\EmbeddedTestLevel1;
use Documents\Functional\EmbeddedTestLevel2;
use Documents\Functional\NotSaved;
use Documents\Functional\NotSavedEmbedded;
use Documents\Functional\VirtualHost;
use Documents\Functional\VirtualHostDirective;
use Documents\Phonenumber;
use Documents\User;
use MongoDB\BSON\ObjectId;

use function assert;

class EmbeddedTest extends BaseTestCase
{
    public function testSetEmbeddedToNull(): void
    {
        $user = new User();
        $user->setId((string) new ObjectId());
        $user->setUsername('jwage');
        $user->setAddress(null);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();
        $userId = $user->getId();

        $user = $this->dm->getRepository(User::class)->find($userId);
        self::assertEquals($userId, $user->getId());
        self::assertNull($user->getAddress());
    }

    public function testFlushEmbedded(): void
    {
        $test       = new EmbeddedTestLevel0();
        $test->name = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(EmbeddedTestLevel0::class)->findOneBy(['name' => 'test']);
        self::assertInstanceOf(EmbeddedTestLevel0::class, $test);

        // Adding this flush here makes level1 not to be inserted.
        $this->dm->flush();

        $level1         = new EmbeddedTestLevel1();
        $level1->name   = 'test level1 #1';
        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(EmbeddedTestLevel0::class, $test->id);
        self::assertInstanceOf(EmbeddedTestLevel0::class, $test);
        self::assertInstanceOf(EmbeddedTestLevel1::class, $test->level1[0]);

        $test->level1[0]->name = 'changed';
        $level1                = new EmbeddedTestLevel1();
        $level1->name          = 'testing';
        $test->level1->add($level1);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(EmbeddedTestLevel0::class, $test->id);
        self::assertCount(2, $test->level1);
        self::assertEquals('changed', $test->level1[0]->name);
        self::assertEquals('testing', $test->level1[1]->name);

        unset($test->level1[0]);
        $this->dm->flush();
        $this->dm->clear();

        self::assertCount(1, $test->level1);
    }

    public function testOneEmbedded(): void
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

        $user = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId())
            ->getQuery()
            ->getSingleResult();
        assert($user instanceof User);
        self::assertNotNull($user);
        self::assertEquals($addressClone, $user->getAddress());

        $oldAddress = $user->getAddress();
        $address    = new Address();
        $address->setAddress('Someplace else');
        $user->setAddress($address);
        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($user);
        self::assertNotEmpty($changeSet['address']);
        self::assertSame($oldAddress, $changeSet['address'][0]);
        self::assertSame($user->getAddress(), $changeSet['address'][1]);
    }

    public function testRemoveOneEmbedded(): void
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');

        $user = new User();
        $user->setUsername('jwage');
        $user->setAddress($address);

        $this->dm->persist($user);
        $this->dm->flush();

        $user->removeAddress();
        self::assertNull($user->getAddress());

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId())
            ->getQuery()
            ->getSingleResult();
        assert($user instanceof User);
        self::assertNotNull($user);
        self::assertNull($user->getAddress());
    }

    public function testManyEmbedded(): void
    {
        $user = new User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId())
            ->getQuery()
            ->getSingleResult();
        assert($user2 instanceof User);
        self::assertNotNull($user2);
        self::assertEquals($user->getPhonenumbers()->toArray(), $user2->getPhonenumbers()->toArray());
    }

    public function testPostRemoveEventOnEmbeddedManyDocument(): void
    {
        // create a test document
        $test       = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1         = new EmbeddedTestLevel1();
        $level1->name   = 'test level1 #1';
        $test->level1[] = $level1;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder($test::class)
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        assert($test instanceof EmbeddedTestLevel0b);
        $level1 = $test->level1[0];

        // $test->level1[0] is available
        self::assertEquals('test level1 #1', $level1->name);

        // remove all level1 from test
        $test->level1->clear();
        $this->dm->flush();

        // verify that level1 lifecycle callbacks have been called
        self::assertTrue($level1->preRemove, 'the removed embedded document executed the PreRemove lifecycle callback');
        self::assertTrue($level1->postRemove, 'the removed embedded document executed the PostRemove lifecycle callback');
    }

    public function testRemoveEmbeddedManyDocument(): void
    {
        // create a test document
        $test       = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1         = new EmbeddedTestLevel1();
        $level1->name   = 'test level1 #1';
        $test->level1[] = $level1;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder($test::class)
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();

        assert($test instanceof EmbeddedTestLevel0b);
        // $test->level1[0] is available
        self::assertEquals('test level1 #1', $test->level1[0]->name);

        // remove all level1 from test
        $test->level1->clear();
        $this->dm->flush();
        $this->dm->clear();

        // verify that test has no more level1
        self::assertEquals(0, $test->level1->count());

        // retrieve test
        $test = $this->dm->createQueryBuilder($test::class)
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();

        assert($test instanceof EmbeddedTestLevel0b);

        self::assertInstanceOf(PersistentCollection::class, $test->level1);

        // verify that test has no more level1
        self::assertEquals(0, $test->level1->count());
    }

    public function testRemoveDeepEmbeddedManyDocument(): void
    {
        // create a test document
        $test       = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1          = new EmbeddedTestLevel1();
        $level1->name    = 'test level1 #1';
        $test->oneLevel1 = $level1;

        // embed one level2 in level1
        $level2           = new EmbeddedTestLevel2();
        $level2->name     = 'test level2 #1';
        $level1->level2[] = $level2;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder($test::class)
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        assert($test instanceof EmbeddedTestLevel0b);
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        // $test->oneLevel1->level2[0] is available
        self::assertEquals('test level2 #1', $level2->name);

        // remove all level2 from level1
        $level1->level2->clear();
        $this->dm->flush();
        $this->dm->clear();

        // verify that level1 has no more level2
        self::assertEquals(0, $level1->level2->count());

        // retrieve test
        $test = $this->dm->createQueryBuilder($test::class)
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        assert($test instanceof EmbeddedTestLevel0b);
        $level1 = $test->oneLevel1;

        // verify that level1 has no more level2
        self::assertEquals(0, $level1->level2->count());
    }

    public function testPostRemoveEventOnDeepEmbeddedManyDocument(): void
    {
        // create a test document
        $test       = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1          = new EmbeddedTestLevel1();
        $level1->name    = 'test level1 #1';
        $test->oneLevel1 = $level1;

        // embed one level2 in level1
        $level2           = new EmbeddedTestLevel2();
        $level2->name     = 'test level2 #1';
        $level1->level2[] = $level2;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQueryBuilder($test::class)
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        assert($test instanceof EmbeddedTestLevel0b);
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        // $test->oneLevel1->level2[0] is available
        self::assertEquals('test level2 #1', $level2->name);

        // remove all level2 from level1
        $level1->level2->clear();
        $this->dm->flush();

        // verify that level2 lifecycle callbacks have been called
        self::assertTrue($level2->preRemove, 'the removed embedded document executed the PreRemove lifecycle callback');
        self::assertTrue($level2->postRemove, 'the removed embedded document executed the PostRemove lifecycle callback');
    }

    public function testEmbeddedLoadEvents(): void
    {
        // create a test document
        $test       = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        $level1          = new EmbeddedTestLevel1();
        $level1->name    = 'test level1 #1';
        $test->oneLevel1 = $level1;

        $level2           = new EmbeddedTestLevel2();
        $level2->name     = 'test level2 #1';
        $level1->level2[] = $level2;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQueryBuilder($test::class)
            ->field('id')->equals($test->id)
            ->getQuery()
            ->getSingleResult();
        assert($test instanceof EmbeddedTestLevel0b);
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        self::assertTrue($level1->preLoad);
        self::assertTrue($level1->postLoad);
        self::assertTrue($level2->preLoad);
        self::assertTrue($level2->postLoad);
    }

    public function testEmbeddedDocumentChangesParent(): void
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $user = new User();
        $user->setUsername('jwagettt');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());
        self::assertNotNull($user);
        $address = $user->getAddress();
        $address->setAddress('changed');

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find(User::class, $user->getId());
        self::assertEquals('changed', $user->getAddress()->getAddress());
    }

    public function testRemoveEmbeddedDocument(): void
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

        $check = $this->dm->getDocumentCollection(User::class)->findOne();
        self::assertEmpty($check['phonenumbers']);
        self::assertNull($check['addressNullable']);
        self::assertArrayNotHasKey('address', $check);
    }

    public function testRemoveAddDeepEmbedded(): void
    {
        $vhost = new VirtualHost();

        $directive1 = new VirtualHostDirective('DirectoryIndex', 'index.php');
        $vhost->getVHostDirective()->addDirective($directive1);

        $directive2 = new VirtualHostDirective('Directory', '/var/www/html');
        $directive2->addDirective(new VirtualHostDirective('AllowOverride', 'All'));
        $vhost->getVHostDirective()->addDirective($directive2);

        $directive3 = new VirtualHostDirective('Directory', '/var/www/html');
        $directive3->addDirective(new VirtualHostDirective('RewriteEngine', 'on'));
        $vhost->getVHostDirective()->addDirective($directive3);

        $this->dm->persist($vhost);
        $this->dm->flush();

        $vhost->getVHostDirective()->removeDirective($directive2);

        $directive4 = new VirtualHostDirective('Directory', '/var/www/html');
        $directive4->addDirective(new VirtualHostDirective('RewriteEngine', 'on'));
        $vhost->getVHostDirective()->addDirective($directive4);

        $this->dm->flush();
        $this->dm->clear();

        $vhost = $this->dm->find(VirtualHost::class, $vhost->getId());

        foreach ($vhost->getVHostDirective()->getDirectives() as $directive) {
            self::assertNotEmpty($directive->getName());
        }
    }

    public function testEmbeddedDocumentNotSavedFields(): void
    {
        $document                     = new NotSaved();
        $document->embedded           = new NotSavedEmbedded();
        $document->embedded->name     = 'foo';
        $document->embedded->notSaved = 'bar';

        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find(NotSaved::class, $document->id);

        self::assertEquals('foo', $document->embedded->name);
        self::assertNull($document->embedded->notSaved);
    }

    public function testChangeEmbedOneDocumentId(): void
    {
        $originalId = (string) new ObjectId();

        $test            = new ChangeEmbeddedIdTest();
        $test->embed     = new EmbeddedDocumentWithId();
        $test->embed->id = $originalId;
        $this->dm->persist($test);

        $this->dm->flush();

        $newId = (string) new ObjectId();

        $test->embed->id = $newId;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find($test::class, $test->id);

        self::assertEquals($newId, $test->embed->id);
    }

    public function testChangeEmbedManyDocumentId(): void
    {
        $originalId = (string) new ObjectId();

        $test                   = new ChangeEmbeddedIdTest();
        $test->embedMany[]      = new EmbeddedDocumentWithId();
        $test->embedMany[0]->id = $originalId;
        $this->dm->persist($test);

        $this->dm->flush();

        $newId = (string) new ObjectId();

        $test->embedMany[0]->id = $newId;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find($test::class, $test->id);

        self::assertEquals($newId, $test->embedMany[0]->id);
    }

    public function testEmbeddedDocumentsWithSameIdAreNotSameInstance(): void
    {
        $originalId = (string) new ObjectId();

        $test              = new ChangeEmbeddedIdTest();
        $test->embed       = new EmbeddedDocumentWithId();
        $test->embedMany[] = $test->embed;
        $test->embedMany[] = $test->embed;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find($test::class, $test->id);

        self::assertNotSame($test->embed, $test->embedMany[0]);
        self::assertNotSame($test->embed, $test->embedMany[1]);
    }

    public function testWhenCopyingManyEmbedSubDocumentsFromOneDocumentToAnotherWillNotAffectTheSourceDocument(): void
    {
        $test1 = new ChangeEmbeddedIdTest();

        $embedded         = new EmbeddedDocumentWithId();
        $embedded->id     = (string) new ObjectId();
        $test1->embedMany = [$embedded];

        $this->dm->persist($test1);
        $this->dm->flush();

        assert($test1->embedMany instanceof Collection);

        $test2            = new ChangeEmbeddedIdTest();
        $test2->embedMany = $test1->embedMany; //using clone will work
        $this->dm->persist($test2);

        self::assertNotSame($test1->embedMany->first(), $test2->embedMany->first());

        $this->dm->flush();

        //do some operations on test1
        $this->dm->persist($test1);
        $this->dm->flush();

        $this->dm->clear(); //get clean results from mongo
        $test1 = $this->dm->find($test1::class, $test1->id);

        self::assertCount(1, $test1->embedMany);
    }

    public function testReusedEmbeddedDocumentsAreClonedInFact(): void
    {
        $test1 = new ChangeEmbeddedIdTest();
        $test2 = new ChangeEmbeddedIdTest();

        $embedded     = new EmbeddedDocumentWithId();
        $embedded->id = (string) new ObjectId();

        $test1->embed = $embedded;
        $test2->embed = $embedded;

        $this->dm->persist($test1);
        $this->dm->persist($test2);

        $this->dm->flush();

        self::assertNotSame($test1->embed, $test2->embed);

        $originalTest1 = $this->uow->getOriginalDocumentData($test1);
        self::assertSame($originalTest1['embed'], $test1->embed);
        $originalTest2 = $this->uow->getOriginalDocumentData($test2);
        self::assertSame($originalTest2['embed'], $test2->embed);
    }

    public function testEmbeddedDocumentWithDifferentFieldNameAnnotation(): void
    {
        $test1 = new ChangeEmbeddedWithNameAnnotationTest();

        $embedded     = new EmbeddedDocumentWithId();
        $embedded->id = (string) new ObjectId();

        $firstEmbedded        = new EmbeddedDocumentWithAnotherEmbedded();
        $firstEmbedded->embed = $embedded;

        $secondEmbedded = clone $firstEmbedded;

        $test1->embedOne = $firstEmbedded;
        $test1->embedTwo = $secondEmbedded;

        $this->dm->persist($test1);

        $this->dm->flush();

        $test1Data = $this->dm->createQueryBuilder(ChangeEmbeddedWithNameAnnotationTest::class)
            ->hydrate(false)
            ->field('id')
            ->equals($test1->id)
            ->getQuery()
            ->getSingleResult();

        self::assertIsArray($test1Data);

        self::assertArrayHasKey('m_id', $test1Data['embedOne']);
    }
}

#[ODM\Document]
class ChangeEmbeddedIdTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var EmbeddedDocumentWithId|null */
    #[ODM\EmbedOne(targetDocument: EmbeddedDocumentWithId::class)]
    public $embed;

    /** @var Collection<int, EmbeddedDocumentWithId>|array<EmbeddedDocumentWithId> */
    #[ODM\EmbedMany(targetDocument: EmbeddedDocumentWithId::class)]
    public $embedMany;

    public function __construct()
    {
        $this->embedMany = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class EmbeddedDocumentWithId
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}

#[ODM\Document]
class ChangeEmbeddedWithNameAnnotationTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var EmbeddedDocumentWithAnotherEmbedded|null */
    #[ODM\EmbedOne(targetDocument: EmbeddedDocumentWithAnotherEmbedded::class)]
    public $embedOne;

    /** @var EmbeddedDocumentWithAnotherEmbedded|null */
    #[ODM\EmbedOne(targetDocument: EmbeddedDocumentWithAnotherEmbedded::class)]
    public $embedTwo;
}

#[ODM\EmbeddedDocument]
class EmbeddedDocumentWithAnotherEmbedded
{
    /** @var EmbeddedDocumentWithId|null */
    #[ODM\EmbedOne(targetDocument: EmbeddedDocumentWithId::class, name: 'm_id')]
    public $embed;
}
