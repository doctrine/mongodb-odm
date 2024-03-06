<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'users')]
#[ODM\InheritanceType('COLLECTION_PER_CLASS')]
class VersionedUser extends User
{
    /** @var int|null */
    #[ODM\Field(type: 'int')]
    #[ODM\Version]
    protected $version;

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }
}
