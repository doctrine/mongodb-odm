<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Functional\Embedded;

#[ODM\EmbeddedDocument]
final class FileMetadata
{
    /** @var User */
    #[ODM\ReferenceOne(targetDocument: User::class, cascade: ['persist'])]
    private $owner;

    /** @var Embedded */
    #[ODM\EmbedOne(targetDocument: Embedded::class)]
    private $embedOne;

    public function __construct()
    {
        $this->embedOne = new Embedded();
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
    }

    public function getEmbedOne(): Embedded
    {
        return $this->embedOne;
    }
}
