<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

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

        $this->assertFalse($this->dm->contains($user));

        $user->name = 'Roman B.';

        //$this->assertEquals(UnitOfWork::STATE_DETACHED, $this->dm->getUnitOfWork()->getEntityState($user));

        $user2 = $this->dm->merge($user);

        $this->assertNotSame($user, $user2);
        $this->assertTrue($this->dm->contains($user2));
        $this->assertEquals('Roman B.', $user2->name);
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
        $this->assertTrue($this->dm->contains($user));
        $this->assertTrue($user->phonenumbers->isInitialized());

        $serialized = serialize($user);

        $this->dm->clear();
        $this->assertFalse($this->dm->contains($user));
        unset($user);

        $user = unserialize($serialized);

        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '56789';
        $user->addPhonenumber($ph2);
        $this->assertCount(2, $user->getPhonenumbers());
        $this->assertFalse($this->dm->contains($user));

        $this->dm->persist($ph2);

        // Merge back in
        $user = $this->dm->merge($user); // merge cascaded to phonenumbers

        $phonenumbers = $user->getPhonenumbers();

        $this->assertCount(2, $phonenumbers);
        $this->assertSame($user, $phonenumbers[0]->getUser());
        $this->assertSame($user, $phonenumbers[1]->getUser());

        $this->dm->flush();

        $this->assertTrue($this->dm->contains($user));
        $this->assertCount(2, $user->getPhonenumbers());
        $phonenumbers = $user->getPhonenumbers();
        $this->assertTrue($this->dm->contains($phonenumbers[0]));
        $this->assertTrue($this->dm->contains($phonenumbers[1]));
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
        $this->assertInstanceOf(CmsArticle::class, $cmsArticle);
        $this->assertSame('alcaeus', $cmsArticle->user->getUsername());
        $this->dm->clear();

        $cmsArticle = $this->dm->merge($cmsArticle);

        $this->assertSame('alcaeus', $cmsArticle->user->getUsername());
    }
}
