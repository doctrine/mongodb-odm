<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsArticle;
use Documents\CmsPhonenumber;
use Documents\CmsUser;

use function assert;
use function serialize;
use function unserialize;

class DetachedDocumentTest extends BaseTest
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
}
