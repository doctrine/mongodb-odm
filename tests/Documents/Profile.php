<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

#[ODM\Document]
class Profile
{
    /** @var ObjectId|string|null */
    #[ODM\Id]
    private $profileId;

    /** @var string|null */
    #[ODM\Field]
    private $firstName;

    /** @var string|null */
    #[ODM\Field]
    private $lastName;

    /** @var File|null */
    #[ODM\ReferenceOne(targetDocument: File::class, cascade: ['all'])]
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
