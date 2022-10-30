<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

/** @ODM\Document */
class Profile
{
    /**
     * @ODM\Id
     *
     * @var ObjectId|string|null
     */
    private $profileId;

    /**
     * @ODM\Field
     *
     * @var string|null
     */
    private $firstName;

    /**
     * @ODM\Field
     *
     * @var string|null
     */
    private $lastName;

    /**
     * @ODM\ReferenceOne(targetDocument=File::class, cascade={"all"})
     *
     * @var File|null
     */
    private $image;

    public function setProfileId(ObjectId $profileId): void
    {
        $this->profileId = $profileId;
    }

    /** @return ObjectId|string|null */
    public function getProfileId()
    {
        return $this->profileId;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setImage(File $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?File
    {
        return $this->image;
    }
}
