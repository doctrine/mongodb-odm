<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
final class FileMetadata
{
    /**
     * @ODM\ReferenceOne(targetDocument=User::class, cascade={"persist"})
     * @var User
     */
    private $owner;

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }
}
