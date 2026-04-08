<?php

declare(strict_types=1);

namespace Documents;

use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

#[ODM\Document]
class Profile
{
    #[ODM\Id]
    private string $profileId;

    /** @var string|null */
    #[ODM\Field]
    private $firstName;

    /** @var string|null */
    #[ODM\Field]
    private $lastName;

    /** @var File|null */
    #[ODM\ReferenceOne(targetDocument: File::class, cascade: ['all'])]
    private $image;

    /** @var UTCDateTime|DateTimeInterface|string */
    #[ODM\Field(type: 'date')]
    protected $deletedAt;

    public function setProfileId(string|ObjectId $profileId): void
    {
        $this->profileId = (string) $profileId;
    }

    /** @return string|null */
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
