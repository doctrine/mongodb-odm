<?php

namespace Doctrine\Tests\ORM\Functional;

use Documents\CmsUser;
use Documents\CmsPhonenumber;
use Documents\CmsAddress;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

class DetachedDocumentTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSimpleDetachMerge()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        // $user is now detached

        $this->assertFalse($this->dm->contains($user));

        $user->name = 'Roman B.';

        //$this->assertEquals(UnitOfWork::STATE_DETACHED, $this->dm->getUnitOfWork()->getEntityState($user));

        $user2 = $this->dm->merge($user);

        $this->assertFalse($user === $user2);
        $this->assertTrue($this->dm->contains($user2));
        $this->assertEquals('Roman B.', $user2->name);
    }

    public function testSerializeUnserializeModifyMerge()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $ph1 = new CmsPhonenumber;
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

        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = '56789';
        $user->addPhonenumber($ph2);
        $this->assertEquals(2, count($user->getPhonenumbers()));
        $this->assertFalse($this->dm->contains($user));

        $this->dm->persist($ph2);

        // Merge back in
        $user = $this->dm->merge($user); // merge cascaded to phonenumbers

        $phonenumbers = $user->getPhonenumbers();

        $this->assertEquals(2, count($phonenumbers));
        $this->assertSame($user, $phonenumbers[0]->getUser());
        $this->assertSame($user, $phonenumbers[1]->getUser());

        $this->dm->flush();

        $this->assertTrue($this->dm->contains($user));
        $this->assertEquals(2, count($user->getPhonenumbers()));
        $phonenumbers = $user->getPhonenumbers();
        $this->assertTrue($this->dm->contains($phonenumbers[0]));
        $this->assertTrue($this->dm->contains($phonenumbers[1]));
    }

    /**
     * @group DDC-203
     */
    public function testDetachedEntityThrowsExceptionOnFlush()
    {
        $ph = new CmsPhonenumber();
        $ph->phonenumber = '12345';
        $this->dm->persist($ph);
        $this->dm->flush();
        $this->dm->clear();
        $this->dm->persist($ph);
        try {
            $this->dm->flush();
            $this->fail();
        } catch (\Exception $expected) {}
    }

    public function testUninitializedLazyAssociationsAreIgnoredOnMerge()
    {
        $user = new CmsUser;
        $user->name = 'Guilherme';
        $user->username = 'gblanco';
        $user->status = 'developer';

        $address = new CmsAddress;
        $address->city = 'Berlin';
        $address->country = 'Germany';
        $address->street = 'Sesamestreet';
        $address->zip = 12345;
        $address->setUser($user);
        $this->dm->persist($address);
        $this->dm->persist($user);

        $this->dm->flush();
        $this->dm->clear();

        $address2 = $this->dm->find(get_class($address), $address->id);
        $this->assertTrue($address2->user instanceof Proxy);
        $this->assertFalse($address2->user->__isInitialized());
        $detachedAddress2 = unserialize(serialize($address2));
        $this->assertTrue($detachedAddress2->user instanceof Proxy);
        $this->assertFalse($detachedAddress2->user->__isInitialized());

        $managedAddress2 = $this->dm->merge($detachedAddress2);
        $this->assertTrue($managedAddress2->user instanceof Proxy);
        $this->assertFalse($managedAddress2->user === $detachedAddress2->user);
        $this->assertFalse($managedAddress2->user->__isInitialized());
    }
}
