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

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');

        $user = $this->getUserRepository()->find($user->id);
        $user->profile = null;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been removed');
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

        $this->assertNotNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been left as-is');

        $user = $this->getUserRepository()->find($user->id);
        $user->profileNoOrphanRemoval = null;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNotNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been left as-is');
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

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
        $this->assertNotNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been left as-is');
    }

    public function testNoOrphanRemovalOnReferenceMany()
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();

        $user = new OrphanRemovalUser();
        $user->profileManyNoOrphanRemoval[] = $profile1;
        $user->profileManyNoOrphanRemoval[] = $profile2;
        $this->dm->persist($user);
        $this->dm->persist($profile1);
        $this->dm->persist($profile2);
        $this->dm->flush();

        $user->profileManyNoOrphanRemoval->removeElement($profile1);
        $this->dm->flush();

        $this->assertNotNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been left as-is');
        $this->assertNotNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been left as-is');
    }

    public function testOrphanRemovalOnReferenceManyUsingClear()
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

        $user->profileMany->clear();
        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
        $this->assertNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been removed');
    }

    public function testOrphanRemovalOnReferenceManyUsingClearUninitialized()
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

        // Ensure profileMany is uninitialized
        $this->dm->detach($user);
        $user = $this->getUserRepository()->find($user->id);

        $user->profileMany->clear();
        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
        $this->assertNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been removed');
    }

    public function testOrphanRemovalOnReferenceManyUsingClearAndAddingNewElements()
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();
        $profile3 = new OrphanRemovalProfile();

        $user = new OrphanRemovalUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;
        $this->dm->persist($user);
        $this->dm->persist($profile1);
        $this->dm->persist($profile2);
        $this->dm->persist($profile3);
        $this->dm->flush();

        $user->profileMany->clear();
        $user->profileMany->add($profile3);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
        $this->assertNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been removed');
        $this->assertNotNull($this->getProfileRepository()->find($profile3->id), 'Profile 3 should have been created');
    }

    public function testOrphanRemovalOnReferenceManyRemovingAndAddingNewElements()
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();
        $profile3 = new OrphanRemovalProfile();

        $user = new OrphanRemovalUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;
        $this->dm->persist($user);
        $this->dm->persist($profile1);
        $this->dm->persist($profile2);
        $this->dm->persist($profile3);
        $this->dm->flush();

        $user->profileMany->removeElement($profile1);
        $user->profileMany->add($profile3);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
        $this->assertNotNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been left as-is');
        $this->assertNotNull($this->getProfileRepository()->find($profile3->id), 'Profile 3 should have been created');
    }

    public function testOrphanRemovalOnReferenceManyUsingSet()
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();
        $profile3 = new OrphanRemovalProfile();

        $user = new OrphanRemovalUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;
        $this->dm->persist($user);
        $this->dm->persist($profile1);
        $this->dm->persist($profile2);
        $this->dm->persist($profile3);
        $this->dm->flush();

        $user->profileMany->set(0, $profile3);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
        $this->assertNotNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been left as-is');
        $this->assertNotNull($this->getProfileRepository()->find($profile3->id), 'Profile 3 should have been created');
    }

    public function testOrphanRemovalWhenRemovingAndAddingSameElement()
    {
        $profile = new OrphanRemovalProfile();

        $user = new OrphanRemovalUser();
        $user->profileMany[] = $profile;
        $this->dm->persist($user);
        $this->dm->persist($profile);
        $this->dm->flush();

        $user->profileMany->removeElement($profile);
        $user->profileMany->add($profile);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNotNull($this->getProfileRepository()->find($profile->id), 'Profile 1 should not have been removed');
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private function getUserRepository()
    {
        return $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalUser');
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentRepository
     */
    private function getProfileRepository()
    {
        return $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\Functional\OrphanRemovalProfile');
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

    /** @ODM\ReferenceMany(targetDocument="OrphanRemovalProfile", orphanRemoval=false) */
    public $profileManyNoOrphanRemoval = array();
}

/** @ODM\Document */
class OrphanRemovalProfile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}
