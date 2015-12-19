<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Test the orphan removal on embedded documents that contain references with cascade operations.
 */
class OrphanRemovalEmbedTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * Test unsetting an embedOne relationship
     */
    public function testUnsettingEmbedOne()
    {
        $profile = new OrphanRemovalCascadeProfile();
        $address = new OrphanRemovalCascadeAddress();
        $user = new OrphanRemovalCascadeUser();
        $profile->address = $address;
        $user->profile = $profile;

        $this->dm->persist($user);
        $this->dm->flush();

        $user->profile = null;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getAddressRepository()->find($address->id), 'Should have removed the address');
    }

    /**
     * Test Collection::remove() method on an embedMany relationship
     */
    public function testRemoveEmbedMany()
    {
        $profile1 = new OrphanRemovalCascadeProfile();
        $address1 = new OrphanRemovalCascadeAddress();
        $profile1->address = $address1;

        $profile2 = new OrphanRemovalCascadeProfile();
        $address2 = new OrphanRemovalCascadeAddress();
        $profile2->address = $address2;

        $user = new OrphanRemovalCascadeUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;

        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertNotNull($this->getAddressRepository()->find($address1->id), 'Should have cascaded persist to address 1');
        $this->assertNotNull($this->getAddressRepository()->find($address2->id), 'Should have cascaded persist to address 2');

        $user->profileMany->removeElement($profile1);

        $this->dm->flush();
        $this->dm->clear($user);

        $user = $this->getUserRepository()->find($user->id);
        $this->assertNotNull($user, 'Should retrieve user');
        $this->assertFalse($user->profileMany->contains($profile1), 'Should not contain profile 1');
        $this->assertNull($this->getAddressRepository()->find($address1->id), 'Should have removed address 1');
        $this->assertTrue($user->profileMany->contains($profile2), 'Should contain profile 2');
        $this->assertNotNull($this->getAddressRepository()->find($address2->id), 'Should have kept address 2');
    }

    /**
     * Test Collection::clear() method on an embedMany relationship
     */
    public function testClearEmbedMany()
    {
        $profile1 = new OrphanRemovalCascadeProfile();
        $address1 = new OrphanRemovalCascadeAddress();
        $profile1->address = $address1;

        $profile2 = new OrphanRemovalCascadeProfile();
        $address2 = new OrphanRemovalCascadeAddress();
        $profile2->address = $address2;

        $user = new OrphanRemovalCascadeUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;

        $this->dm->persist($user);
        $this->dm->flush();

        $user->profileMany->clear();

        $this->dm->flush();
        $this->dm->clear($user);

        $this->assertNull($this->getAddressRepository()->find($address1->id), 'Should have removed address 1');
        $this->assertNull($this->getAddressRepository()->find($address2->id), 'Should have removed address 2');
    }

    /**
     * Test clearing and adding on an embedMany relationship
     */
    public function testClearAndAddEmbedMany()
    {
        $profile1 = new OrphanRemovalCascadeProfile();
        $address1 = new OrphanRemovalCascadeAddress();
        $profile1->address = $address1;

        $profile2 = new OrphanRemovalCascadeProfile();
        $address2 = new OrphanRemovalCascadeAddress();
        $profile2->address = $address2;

        $profile3 = new OrphanRemovalCascadeProfile();
        $address3 = new OrphanRemovalCascadeAddress();
        $profile3->address = $address3;

        $user = new OrphanRemovalCascadeUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;

        $this->dm->persist($user);
        $this->dm->flush();

        $user->profileMany->clear();
        $user->profileMany->add($profile3);

        $this->dm->flush();
        $this->dm->clear($user);

        $user = $this->getUserRepository()->find($user->id);
        $this->assertNotNull($user, 'Should retrieve user');
        $this->assertFalse($user->profileMany->contains($profile1), 'Should not contain profile 1');
        $this->assertNull($this->getAddressRepository()->find($address1->id), 'Should have removed address 1');
        $this->assertFalse($user->profileMany->contains($profile2), 'Should not contain profile 2');
        $this->assertNull($this->getAddressRepository()->find($address2->id), 'Should have removed address 2');
        $this->assertTrue($user->profileMany->contains($profile3), 'Should contain profile 3');
        $this->assertNotNull($this->getAddressRepository()->find($address3->id), 'Should have added address 3');
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private function getUserRepository()
    {
        return $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalCascadeUser');
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private function getAddressRepository()
    {
        return $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalCascadeAddress');
    }
}

/** @ODM\Document */
class OrphanRemovalCascadeUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="OrphanRemovalCascadeProfile") */
    public $profile;

    /** @ODM\EmbedMany(targetDocument="OrphanRemovalCascadeProfile") */
    public $profileMany = array();
}

/** @ODM\EmbeddedDocument */
class OrphanRemovalCascadeProfile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\ReferenceOne(targetDocument="OrphanRemovalCascadeAddress", orphanRemoval=true, cascade={"all"}) */
    public $address;

    /** @ODM\ReferenceMany(targetDocument="OrphanRemovalCascadeAddress", orphanRemoval=true, cascade={"all"}) */
    public $addressMany;
}

/** @ODM\Document */
class OrphanRemovalCascadeAddress
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}
