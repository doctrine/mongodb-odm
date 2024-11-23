<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Closure;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Event\DocumentNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\Phonenumber;
use Documents\Profile;
use Documents\ProfileNotify;
use Documents\User;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;

use function assert;

class ReferencesTest extends BaseTestCase
{
    public function testManyDeleteReference(): void
    {
        $user = new User();

        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();

        $this->dm->remove($user2);
        $this->dm->flush();

        $qb     = $this->dm->createQueryBuilder(Group::class);
        $query  = $qb->getQuery();
        $groups = $query->execute();
        assert($groups instanceof Iterator);

        self::assertEmpty($groups->toArray());
    }

    public function testLazyLoadReference(): void
    {
        $user    = new User();
        $profile = new Profile();
        $profile->setFirstName('Jonathan');
        $profile->setLastName('Wage');
        $user->setProfile($profile);
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();

        $user = $query->getSingleResult();
        assert($user instanceof User);

        $profile = $user->getProfile();
        assert($profile instanceof Profile);

        self::assertInstanceOf(Profile::class, $profile);
        self::assertTrue(self::isLazyObject($profile));

        $profile->getFirstName();

        self::assertEquals('Jonathan', $profile->getFirstName());
        self::assertEquals('Wage', $profile->getLastName());
    }

    public function testLazyLoadedWithNotifyPropertyChanged(): void
    {
        $user    = new User();
        $profile = new ProfileNotify();
        $profile->setFirstName('Maciej');
        $user->setProfileNotify($profile);
        $user->setUsername('malarzm');

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user    = $this->dm->find($user::class, $user->getId());
        $profile = $user->getProfileNotify();
        self::assertTrue(self::isLazyObject($profile));
        self::assertTrue($this->uow->isUninitializedObject($profile));

        $user->getProfileNotify()->setLastName('Malarz');
        $this->dm->flush();
        $this->dm->clear();

        $profile = $this->dm->find($profile::class, $profile->getProfileId());
        self::assertEquals('Maciej', $profile->getFirstName());
        self::assertEquals('Malarz', $profile->getLastName());
    }

    public function testOneEmbedded(): void
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

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        assert($user2 instanceof User);
        self::assertEquals($user->getAddress(), $user2->getAddress());
    }

    public function testManyEmbedded(): void
    {
        $user = new User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        assert($user2 instanceof User);
        self::assertEquals($user->getPhonenumbers()->toArray(), $user2->getPhonenumbers()->toArray());
    }

    public function testOneReference(): void
    {
        $account = new Account();
        $account->setName('Test Account');

        $user = new User();
        $user->setUsername('jwage');
        $user->setAccount($account);

        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();

        self::assertNotNull($user->getAccount()->getId());

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        self::assertInstanceOf(User::class, $user2);
    }

    public function testManyReference(): void
    {
        $user = new User();
        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getGroups();

        self::assertInstanceOf(PersistentCollection::class, $groups);
        self::assertNotSame('', $groups[0]->getId());
        self::assertNotSame('', $groups[1]->getId());
        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        assert($user2 instanceof User);
        $groups = $user2->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $groups);
        self::assertFalse($groups->isInitialized());

        $groups->count();
        self::assertTrue($groups->isInitialized());

        $groups->isEmpty();
        self::assertTrue($groups->isInitialized());

        $groups = $user2->getGroups();

        self::assertInstanceOf(PersistentCollection::class, $groups);
        self::assertInstanceOf(Group::class, $groups[0]);
        self::assertInstanceOf(Group::class, $groups[1]);

        self::assertTrue($groups->isInitialized());

        unset($groups[0]);
        $groups[1]->setName('test');

        $this->dm->flush();
        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user3 = $query->getSingleResult();
        assert($user3 instanceof User);
        $groups = $user3->getGroups();

        self::assertEquals('test', $groups[0]->getName());
        self::assertCount(1, $groups);
    }

    public function testFlushInitializesEmptyPersistentCollection(): void
    {
        $user = new User();

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->find($user->getId());

        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $groups);
        self::assertTrue($groups->isInitialized(), 'A flushed collection should be initialized');
        self::assertCount(2, $groups);
        self::assertCount(2, $groups->toArray());
    }

    public function testFlushInitializesNotEmptyPersistentCollection(): void
    {
        $user = new User();
        $user->addGroup(new Group('Group'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getRepository(User::class)->find($user->getId());

        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getGroups();
        self::assertInstanceOf(PersistentCollectionInterface::class, $groups);
        self::assertTrue($groups->isInitialized(), 'A flushed collection should be initialized');
        self::assertCount(3, $groups);
        self::assertCount(3, $groups->toArray());
    }

    public function testManyReferenceWithAddToSetStrategy(): void
    {
        $user = new User();
        $user->addUniqueGroup($group1 = new Group('Group 1'));
        $user->addUniqueGroup($group1);
        $user->addUniqueGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();

        $groups = $user->getUniqueGroups();
        self::assertCount(3, $groups);

        self::assertInstanceOf(PersistentCollection::class, $groups);
        self::assertNotSame('', $groups[0]->getId());
        self::assertNotSame('', $groups[1]->getId());
        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user2 = $query->getSingleResult();
        assert($user2 instanceof User);

        $groups = $user2->getUniqueGroups();
        self::assertInstanceOf(PersistentCollection\PersistentCollectionInterface::class, $groups);
        self::assertFalse($groups->isInitialized());

        $groups->count();
        self::assertTrue($groups->isInitialized());

        $groups->isEmpty();
        self::assertTrue($groups->isInitialized());

        self::assertCount(2, $groups);

        self::assertInstanceOf(PersistentCollection::class, $groups);
        self::assertInstanceOf(Group::class, $groups[0]);
        self::assertInstanceOf(Group::class, $groups[1]);

        self::assertTrue($groups->isInitialized());

        unset($groups[0]);
        $groups[1]->setName('test');

        $this->dm->flush();
        $this->dm->clear();

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('id')->equals($user->getId());
        $query = $qb->getQuery();
        $user3 = $query->getSingleResult();
        assert($user3 instanceof User);
        $groups = $user3->getUniqueGroups();

        self::assertEquals('test', $groups[0]->getName());
        self::assertCount(1, $groups);
    }

    public function testSortReferenceManyOwningSide(): void
    {
        $user = new User();
        $user->addGroup(new Group('Group 1'));
        $user->addGroup(new Group('Group 2'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->find($user::class, $user->getId());

        $groups = $user->getSortedAscGroups();
        self::assertEquals(2, $groups->count());
        self::assertEquals('Group 1', $groups[0]->getName());
        self::assertEquals('Group 2', $groups[1]->getName());

        $groups[1]->setName('Group 2a');

        $groups = $user->getSortedDescGroups();
        self::assertEquals(2, $groups->count());
        self::assertEquals('Group 2a', $groups[0]->getName());
        self::assertEquals('Group 1', $groups[1]->getName());
    }

    public function testDocumentNotFoundExceptionWithArrayId(): void
    {
        $test                   = new DocumentWithArrayReference();
        $test->referenceOne     = new DocumentWithArrayId();
        $test->referenceOne->id = ['identifier' => 1];

        $this->dm->persist($test);
        $this->dm->persist($test->referenceOne);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection($test::class);

        $collection->updateOne(
            ['_id' => new ObjectId($test->id)],
            [
                '$set' => [
                    'referenceOne.$id' => ['identifier' => 2],
                ],
            ],
        );

        $test = $this->dm->find($test::class, $test->id);
        self::assertTrue(self::isLazyObject($test->referenceOne));
        $this->expectException(DocumentNotFoundException::class);
        $this->expectExceptionMessage(
            'The "Doctrine\ODM\MongoDB\Tests\Functional\DocumentWithArrayId" document with identifier ' .
            '{"identifier":2} could not be found.',
        );
        $this->uow->initializeObject($test->referenceOne);
    }

    public function testDocumentNotFoundExceptionWithObjectId(): void
    {
        $profile = new Profile();
        $user    = new User();
        $user->setProfile($profile);

        $this->dm->persist($profile);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection($user::class);

        $invalidId = new ObjectId('abcdefabcdefabcdefabcdef');

        $collection->updateOne(
            ['_id' => new ObjectId($user->getId())],
            [
                '$set' => ['profile.$id' => $invalidId],
            ],
        );

        $user    = $this->dm->find($user::class, $user->getId());
        $profile = $user->getProfile();
        self::assertTrue(self::isLazyObject($profile));
        $this->expectException(DocumentNotFoundException::class);
        $this->expectExceptionMessage(
            'The "Documents\Profile" document with identifier "abcdefabcdefabcdefabcdef" could not be found.',
        );
        $this->uow->initializeObject($profile);
    }

    public function testDocumentNotFoundExceptionWithMongoBinDataId(): void
    {
        $test                   = new DocumentWithMongoBinDataReference();
        $test->referenceOne     = new DocumentWithMongoBinDataId();
        $test->referenceOne->id = 'test';

        $this->dm->persist($test);
        $this->dm->persist($test->referenceOne);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection($test::class);

        $invalidBinData = new Binary('testbindata', Binary::TYPE_OLD_BINARY);

        $collection->updateOne(
            ['_id' => new ObjectId($test->id)],
            [
                '$set' => ['referenceOne.$id' => $invalidBinData],
            ],
        );

        $test = $this->dm->find($test::class, $test->id);
        self::assertTrue(self::isLazyObject($test->referenceOne));
        $this->expectException(DocumentNotFoundException::class);
        $this->expectExceptionMessage(
            'The "Doctrine\ODM\MongoDB\Tests\Functional\DocumentWithMongoBinDataId" document with identifier ' .
            '"testbindata" could not be found.',
        );
        $this->uow->initializeObject($test->referenceOne);
    }

    public function testDocumentNotFoundEvent(): void
    {
        $profile = new Profile();
        $user    = new User();
        $user->setProfile($profile);

        $this->dm->persist($profile);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $collection = $this->dm->getDocumentCollection($user::class);

        $invalidId = new ObjectId('abcdefabcdefabcdefabcdef');

        $collection->updateOne(
            ['_id' => new ObjectId($user->getId())],
            [
                '$set' => ['profile.$id' => $invalidId],
            ],
        );

        $user    = $this->dm->find($user::class, $user->getId());
        $profile = $user->getProfile();

        $closure = static function (DocumentNotFoundEventArgs $eventArgs) use ($profile) {
            self::assertFalse($eventArgs->isExceptionDisabled());
            self::assertSame($profile, $eventArgs->getObject());
            $eventArgs->disableException();
        };

        $this->dm->getEventManager()->addEventListener(Events::documentNotFound, new DocumentNotFoundListener($closure));

        self::assertTrue(self::isLazyObject($profile));
        $this->uow->initializeObject($profile);
    }
}

#[ODM\Document]
class DocumentWithArrayReference
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var DocumentWithArrayId|null */
    #[ODM\ReferenceOne(targetDocument: DocumentWithArrayId::class)]
    public $referenceOne;
}

#[ODM\Document]
class DocumentWithArrayId
{
    /** @var array<string, int> */
    #[ODM\Id(strategy: 'none', options: ['type' => 'hash'])]
    public $id;
}


#[ODM\Document]
class DocumentWithMongoBinDataReference
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var DocumentWithMongoBinDataId|null */
    #[ODM\ReferenceOne(targetDocument: DocumentWithMongoBinDataId::class)]
    public $referenceOne;
}

#[ODM\Document]
class DocumentWithMongoBinDataId
{
    /** @var string|null */
    #[ODM\Id(strategy: 'none', options: ['type' => 'bin'])]
    public $id;
}

class DocumentNotFoundListener
{
    public function __construct(private Closure $closure)
    {
    }

    public function documentNotFound(DocumentNotFoundEventArgs $eventArgs): void
    {
        $closure = $this->closure;
        $closure($eventArgs);
    }
}
