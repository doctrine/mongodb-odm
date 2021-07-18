<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function assert;

class OrphanRemovalTest extends BaseTest
{
    public function testOrphanRemoval(): void
    {
        $profile1      = new OrphanRemovalProfile();
        $user          = new OrphanRemovalUser();
        $user->profile = $profile1;
        $this->dm->persist($user);
        $this->dm->persist($user->profile);
        $this->dm->flush();

        $profile2      = new OrphanRemovalProfile();
        $user->profile = $profile2;
        $this->dm->persist($user->profile);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');

        $user          = $this->getUserRepository()->find($user->id);
        $user->profile = null;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been removed');
    }

    public function testNoOrphanRemoval(): void
    {
        $profile1                     = new OrphanRemovalProfile();
        $user                         = new OrphanRemovalUser();
        $user->profileNoOrphanRemoval = $profile1;
        $this->dm->persist($user);
        $this->dm->persist($user->profileNoOrphanRemoval);
        $this->dm->flush();

        $profile2                     = new OrphanRemovalProfile();
        $user->profileNoOrphanRemoval = $profile2;
        $this->dm->persist($user->profileNoOrphanRemoval);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertNotNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been left as-is');

        $user                         = $this->getUserRepository()->find($user->id);
        $user->profileNoOrphanRemoval = null;

        $this->dm->flush();
        $this->dm->clear();

        $this->assertNotNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been left as-is');
    }

    public function testOrphanRemovalOnReferenceMany(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
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

    public function testNoOrphanRemovalOnReferenceMany(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();

        $user                               = new OrphanRemovalUser();
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

    public function testOrphanRemovalOnReferenceManyUsingClear(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
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

    public function testOrphanRemovalOnReferenceManyUsingClearUninitialized(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
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

    public function testOrphanRemovalOnReferenceManyUsingClearAndAddingNewElements(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();
        $profile3 = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
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

    public function testOrphanRemovalOnReferenceManyRemovingAndAddingNewElements(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();
        $profile3 = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
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

    public function testOrphanRemovalOnReferenceManyUsingSet(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();
        $profile3 = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
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

    public function testOrphanRemovalWhenRemovingAndAddingSameElement(): void
    {
        $profile = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
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

    public function testOrphanRemovalOnRemoveWithoutCascade(): void
    {
        $profile1      = new OrphanRemovalProfile();
        $user          = new OrphanRemovalUser();
        $user->profile = $profile1;
        $this->dm->persist($user);
        $this->dm->persist($user->profile);
        $this->dm->flush();

        $this->dm->remove($user);
        $this->dm->flush();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
    }

    public function testOrphanRemovalReferenceManyOnRemoveWithoutCascade(): void
    {
        $profile1 = new OrphanRemovalProfile();
        $profile2 = new OrphanRemovalProfile();

        $user                = new OrphanRemovalUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;
        $this->dm->persist($user);
        $this->dm->persist($profile1);
        $this->dm->persist($profile2);
        $this->dm->flush();

        $this->dm->remove($user);
        $this->dm->flush();

        $this->assertNull($this->getProfileRepository()->find($profile1->id), 'Profile 1 should have been removed');
        $this->assertNull($this->getProfileRepository()->find($profile2->id), 'Profile 2 should have been removed');
    }

    private function getUserRepository(): DocumentRepository
    {
        $repository = $this->dm->getRepository(OrphanRemovalUser::class);

        assert($repository instanceof DocumentRepository);

        return $repository;
    }

    private function getProfileRepository(): DocumentRepository
    {
        $repository = $this->dm->getRepository(OrphanRemovalProfile::class);

        assert($repository instanceof DocumentRepository);

        return $repository;
    }
}

/** @ODM\Document */
class OrphanRemovalUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=OrphanRemovalProfile::class, orphanRemoval=true) */
    public $profile;

    /** @ODM\ReferenceOne(targetDocument=OrphanRemovalProfile::class, orphanRemoval=false) */
    public $profileNoOrphanRemoval;

    /** @ODM\ReferenceMany(targetDocument=OrphanRemovalProfile::class, orphanRemoval=true) */
    public $profileMany = [];

    /** @ODM\ReferenceMany(targetDocument=OrphanRemovalProfile::class, orphanRemoval=false) */
    public $profileManyNoOrphanRemoval = [];
}

/** @ODM\Document */
class OrphanRemovalProfile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}
