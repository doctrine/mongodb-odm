<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function assert;

/**
 * Test the orphan removal on embedded documents that contain references with cascade operations.
 */
class OrphanRemovalEmbedTest extends BaseTest
{
    /**
     * Test unsetting an embedOne relationship
     */
    public function testUnsettingEmbedOne(): void
    {
        $profile          = new OrphanRemovalCascadeProfile();
        $address          = new OrphanRemovalCascadeAddress();
        $user             = new OrphanRemovalCascadeUser();
        $profile->address = $address;
        $user->profile    = $profile;

        $this->dm->persist($user);
        $this->dm->flush();

        $user->profile = null;

        $this->dm->flush();
        $this->dm->clear();

        self::assertNull($this->getAddressRepository()->find($address->id), 'Should have removed the address');
    }

    /**
     * Test Collection::remove() method on an embedMany relationship
     */
    public function testRemoveEmbedMany(): void
    {
        $profile1          = new OrphanRemovalCascadeProfile();
        $address1          = new OrphanRemovalCascadeAddress();
        $profile1->address = $address1;

        $profile2          = new OrphanRemovalCascadeProfile();
        $address2          = new OrphanRemovalCascadeAddress();
        $profile2->address = $address2;

        $user                = new OrphanRemovalCascadeUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;

        $this->dm->persist($user);
        $this->dm->flush();

        self::assertNotNull($this->getAddressRepository()->find($address1->id), 'Should have cascaded persist to address 1');
        self::assertNotNull($this->getAddressRepository()->find($address2->id), 'Should have cascaded persist to address 2');

        $user->profileMany->removeElement($profile1);

        $this->dm->flush();

        $user = $this->getUserRepository()->find($user->id);
        self::assertNotNull($user, 'Should retrieve user');
        self::assertFalse($user->profileMany->contains($profile1), 'Should not contain profile 1');
        self::assertNull($this->getAddressRepository()->find($address1->id), 'Should have removed address 1');
        self::assertTrue($user->profileMany->contains($profile2), 'Should contain profile 2');
        self::assertNotNull($this->getAddressRepository()->find($address2->id), 'Should have kept address 2');
    }

    /**
     * Test Collection::clear() method on an embedMany relationship
     */
    public function testClearEmbedMany(): void
    {
        $profile1          = new OrphanRemovalCascadeProfile();
        $address1          = new OrphanRemovalCascadeAddress();
        $profile1->address = $address1;

        $profile2          = new OrphanRemovalCascadeProfile();
        $address2          = new OrphanRemovalCascadeAddress();
        $profile2->address = $address2;

        $user                = new OrphanRemovalCascadeUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;

        $this->dm->persist($user);
        $this->dm->flush();

        $user->profileMany->clear();

        $this->dm->flush();
        $this->dm->clear(OrphanRemovalCascadeUser::class);

        self::assertNull($this->getAddressRepository()->find($address1->id), 'Should have removed address 1');
        self::assertNull($this->getAddressRepository()->find($address2->id), 'Should have removed address 2');
    }

    /**
     * Test clearing and adding on an embedMany relationship
     */
    public function testClearAndAddEmbedMany(): void
    {
        $profile1          = new OrphanRemovalCascadeProfile();
        $address1          = new OrphanRemovalCascadeAddress();
        $profile1->address = $address1;

        $profile2          = new OrphanRemovalCascadeProfile();
        $address2          = new OrphanRemovalCascadeAddress();
        $profile2->address = $address2;

        $profile3          = new OrphanRemovalCascadeProfile();
        $address3          = new OrphanRemovalCascadeAddress();
        $profile3->address = $address3;

        $user                = new OrphanRemovalCascadeUser();
        $user->profileMany[] = $profile1;
        $user->profileMany[] = $profile2;

        $this->dm->persist($user);
        $this->dm->flush();

        $user->profileMany->clear();
        $user->profileMany->add($profile3);

        $this->dm->flush();

        $user = $this->getUserRepository()->find($user->id);
        self::assertNotNull($user, 'Should retrieve user');
        self::assertFalse($user->profileMany->contains($profile1), 'Should not contain profile 1');
        self::assertNull($this->getAddressRepository()->find($address1->id), 'Should have removed address 1');
        self::assertFalse($user->profileMany->contains($profile2), 'Should not contain profile 2');
        self::assertNull($this->getAddressRepository()->find($address2->id), 'Should have removed address 2');
        self::assertTrue($user->profileMany->contains($profile3), 'Should contain profile 3');
        self::assertNotNull($this->getAddressRepository()->find($address3->id), 'Should have added address 3');
    }

    /** @return DocumentRepository<OrphanRemovalCascadeUser> */
    private function getUserRepository(): DocumentRepository
    {
        $repository = $this->dm->getRepository(OrphanRemovalCascadeUser::class);

        assert($repository instanceof DocumentRepository);

        return $repository;
    }

    /** @return DocumentRepository<OrphanRemovalCascadeAddress> */
    private function getAddressRepository(): DocumentRepository
    {
        $repository = $this->dm->getRepository(OrphanRemovalCascadeAddress::class);

        assert($repository instanceof DocumentRepository);

        return $repository;
    }
}

/** @ODM\Document */
class OrphanRemovalCascadeUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=OrphanRemovalCascadeProfile::class)
     *
     * @var OrphanRemovalCascadeProfile|null
     */
    public $profile;

    /**
     * @ODM\EmbedMany(targetDocument=OrphanRemovalCascadeProfile::class)
     *
     * @var Collection<int, OrphanRemovalCascadeProfile>|array<OrphanRemovalCascadeProfile>
     */
    public $profileMany = [];
}

/** @ODM\EmbeddedDocument */
class OrphanRemovalCascadeProfile
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=OrphanRemovalCascadeAddress::class, orphanRemoval=true, cascade={"all"})
     *
     * @var OrphanRemovalCascadeAddress|null
     */
    public $address;

    /**
     * @ODM\ReferenceMany(targetDocument=OrphanRemovalCascadeAddress::class, orphanRemoval=true, cascade={"all"})
     *
     * @var Collection<int, OrphanRemovalCascadeAddress>
     */
    public $addressMany;
}

/** @ODM\Document */
class OrphanRemovalCascadeAddress
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}
