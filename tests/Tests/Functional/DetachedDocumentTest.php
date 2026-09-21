<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Registry\PersistenceState;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsAddress;
use Documents\CmsArticle;
use Documents\CmsPhonenumber;
use Documents\CmsUser;
use Documents\VersionedUser;
use InvalidArgumentException;

use function assert;
use function serialize;
use function unserialize;

class DetachedDocumentTest extends BaseTestCase
{
    public function testSimpleDetachMerge(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'dev';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        // $user is now detached

        self::assertFalse($this->dm->contains($user));

        $user->name = 'Roman B.';

        //self::assertEquals(UnitOfWork::STATE_DETACHED, $this->dm->getUnitOfWork()->getEntityState($user));

        $user2 = $this->dm->merge($user);

        self::assertNotSame($user, $user2);
        self::assertTrue($this->dm->contains($user2));
        self::assertEquals('Roman B.', $user2->name);
    }

    public function testSerializeUnserializeModifyMerge(): void
    {
        $user           = new CmsUser();
        $user->name     = 'Guilherme';
        $user->username = 'gblanco';
        $user->status   = 'developer';

        $ph1              = new CmsPhonenumber();
        $ph1->phonenumber = '1234';
        $user->addPhonenumber($ph1);

        $this->dm->persist($user);
        $this->dm->flush();
        self::assertTrue($this->dm->contains($user));
        self::assertInstanceOf(PersistentCollectionInterface::class, $user->phonenumbers);
        self::assertTrue($user->phonenumbers->isInitialized());

        $serialized = serialize($user);

        $this->dm->clear();
        self::assertFalse($this->dm->contains($user));
        unset($user);

        $user = unserialize($serialized);

        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '56789';
        $user->addPhonenumber($ph2);
        self::assertCount(2, $user->getPhonenumbers());
        self::assertFalse($this->dm->contains($user));

        $this->dm->persist($ph2);

        // Merge back in
        $user = $this->dm->merge($user); // merge cascaded to phonenumbers

        $phonenumbers = $user->getPhonenumbers();

        self::assertCount(2, $phonenumbers);
        self::assertSame($user, $phonenumbers[0]->getUser());
        self::assertSame($user, $phonenumbers[1]->getUser());

        $this->dm->flush();

        self::assertTrue($this->dm->contains($user));
        self::assertCount(2, $user->getPhonenumbers());
        $phonenumbers = $user->getPhonenumbers();
        self::assertTrue($this->dm->contains($phonenumbers[0]));
        self::assertTrue($this->dm->contains($phonenumbers[1]));
    }

    public function testMergeWithReference(): void
    {
        $cmsUser           = new CmsUser();
        $cmsUser->username = 'alcaeus';

        $cmsArticle = new CmsArticle();
        $cmsArticle->setAuthor($cmsUser);

        $this->dm->persist($cmsUser);
        $this->dm->persist($cmsArticle);
        $this->dm->flush();
        $this->dm->clear();

        $cmsArticle = $this->dm->find(CmsArticle::class, $cmsArticle->id);
        assert($cmsArticle instanceof CmsArticle);
        self::assertInstanceOf(CmsArticle::class, $cmsArticle);
        self::assertSame('alcaeus', $cmsArticle->user->getUsername());
        $this->dm->clear();

        $cmsArticle = $this->dm->merge($cmsArticle);

        self::assertSame('alcaeus', $cmsArticle->user->getUsername());
    }

    public function testMergeThrowsWhenManagedCopyWasRemoved(): void
    {
        $user           = new CmsUser();
        $user->username = 'alcaeus';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        // scheduleForDelete() removes a document from the identity map as
        // soon as remove() is called, so find() can never observe a
        // STATE_REMOVED document through the normal remove()+merge() flow.
        // Simulate the state directly to exercise doMerge()'s guard.
        $uow              = $this->dm->getUnitOfWork();
        $reregistered     = new CmsUser();
        $reregistered->id = $user->id;
        $uow->registerManaged($reregistered, $user->id, ['id' => $user->id]);

        $this->dm->getDocumentRegistry()->getOrCreateObjectState($reregistered)->state = PersistenceState::Removed;

        $detachedCopy           = new CmsUser();
        $detachedCopy->id       = $user->id;
        $detachedCopy->username = 'alcaeus-detached';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Removed entity detected during merge. Cannot merge with a removed entity.');

        $this->dm->merge($detachedCopy);
    }

    public function testMergeThrowsOnVersionMismatch(): void
    {
        $user = new VersionedUser();
        $user->setUsername('alcaeus');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $detachedCopy = new VersionedUser();
        $detachedCopy->setId($user->getId());
        $detachedCopy->setUsername('alcaeus-detached');
        $detachedCopy->setVersion($user->getVersion() + 1);

        $this->expectException(LockException::class);

        $this->dm->merge($detachedCopy);
    }

    public function testMergeInitializesUninitializedDocument(): void
    {
        $user           = new CmsUser();
        $user->username = 'alcaeus';
        $this->dm->persist($user);
        $this->dm->flush();
        $id = $user->id;
        // getReference() returns the already-loaded instance directly if it's
        // still in the identity map, so clear it first to force a real proxy.
        $this->dm->clear();

        $proxy = $this->dm->getReference(CmsUser::class, $id);
        self::assertTrue($this->dm->getUnitOfWork()->isUninitializedObject($proxy));

        // Clear again so the proxy is detached (but still uninitialized) when merged.
        $this->dm->clear();

        $merged = $this->dm->merge($proxy);

        // doMerge() initializes an uninitialized $document before reading its
        // fields, so the original proxy is no longer uninitialized afterwards.
        self::assertFalse($this->dm->getUnitOfWork()->isUninitializedObject($proxy));
        self::assertSame('alcaeus', $merged->username);
    }

    public function testMergeReattachesDetachedNonCascadedReference(): void
    {
        $address        = new CmsAddress();
        $address->city  = 'Berlin';
        $user           = new CmsUser();
        $user->username = 'alcaeus';
        $user->address  = $address;
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        // A plain (non-proxy) detached CmsAddress carrying the same id as the
        // persisted one. CmsUser::$address only cascades "persist", not
        // "merge", so its value should be reattached by reference rather than
        // field-merged.
        $detachedAddress       = new CmsAddress();
        $detachedAddress->id   = $address->id;
        $detachedAddress->city = 'Munich';

        $detachedUser           = new CmsUser();
        $detachedUser->id       = $user->id;
        $detachedUser->username = 'alcaeus';
        $detachedUser->address  = $detachedAddress;

        $merged = $this->dm->merge($detachedUser);

        self::assertNotSame($detachedAddress, $merged->address);
        self::assertSame((string) $address->id, (string) $merged->address->id);
        self::assertTrue($this->dm->contains($merged->address));

        $this->dm->flush();
        $this->dm->clear();

        // The address itself must not have been field-merged, since the
        // association doesn't cascade merge.
        $reloadedAddress = $this->dm->find(CmsAddress::class, $address->id);
        self::assertSame('Berlin', $reloadedAddress->city);
    }

    public function testMergeIgnoresStaticProperties(): void
    {
        $document       = new DocumentWithStaticProperty();
        $document->name = 'foo';
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $document->name = 'bar';

        $merged = $this->dm->merge($document);

        self::assertSame('bar', $merged->name);
        self::assertSame('staticValue', DocumentWithStaticProperty::$staticProperty);
    }
}

#[ODM\Document]
class DocumentWithStaticProperty
{
    public static string $staticProperty = 'staticValue';

    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    public string $name;
}
