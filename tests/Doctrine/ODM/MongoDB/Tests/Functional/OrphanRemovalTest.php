<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class OrphanRemovalTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testOrphanRemoval()
    {
        $profile1 = new OrphanRemovalProfile();
        $user = new OrphanRemovalUser();
        $user->profile = $profile1;
        $this->dm->persist($user);
        $this->dm->persist($user->profile);
        $this->dm->flush();

        $profile2 = new OrphanRemovalProfile();
        $user->profile = $profile2;
        $this->dm->persist($user->profile);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalProfile')->find($profile1->id);
        $this->assertNull($check);

        $user = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalUser')->find($user->id);
        $user->profile = null;
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalProfile')->find($profile2->id);
        $this->assertNull($check);
    }

    public function testNoOrphanRemoval()
    {
        $profile1 = new OrphanRemovalProfile();
        $user = new OrphanRemovalUser();
        $user->profileNoOrphanRemoval = $profile1;
        $this->dm->persist($user);
        $this->dm->persist($user->profileNoOrphanRemoval);
        $this->dm->flush();

        $profile2 = new OrphanRemovalProfile();
        $user->profileNoOrphanRemoval = $profile2;
        $this->dm->persist($user->profileNoOrphanRemoval);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalProfile')->find($profile1->id);
        $this->assertNotNull($check);

        $user = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalUser')->find($user->id);
        $user->profileNoOrphanRemoval = null;
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalProfile')->find($profile2->id);
        $this->assertNotNull($check);
    }

    public function testOrphanRemovalOnReferenceMany()
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();

        $user = new OrphanRemovalUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;
        $this->dm->persist($user);
        $this->dm->persist($profile1);
        $this->dm->persist($profile2);
        $this->dm->flush();

        $user->profileMany->removeElement($profile1);
        $this->dm->flush();

        $check = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalProfile')->find($profile1->id);
        $this->assertNull($check);

        $check = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalProfile')->find($profile2->id);
        $this->assertNotNull($check);
    }
}

/** @ODM\Document */
class OrphanRemovalUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="OrphanRemovalProfile", orphanRemoval=true) */
    public $profile;

    /** @ODM\ReferenceOne(targetDocument="OrphanRemovalProfile", orphanRemoval=false) */
    public $profileNoOrphanRemoval;

    /** @ODM\ReferenceMany(targetDocument="OrphanRemovalProfile", orphanRemoval=true) */    
    public $profileMany = array();
}

/** @ODM\Document */
class OrphanRemovalProfile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;
}
