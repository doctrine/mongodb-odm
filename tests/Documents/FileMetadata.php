<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Functional\Embedded;

/** @ODM\EmbeddedDocument */
final class FileMetadata
{
    /**
     * @ODM\ReferenceOne(targetDocument=User::class, cascade={"persist"})
     *
     * @var User
     */
    private $owner;

    /**
     * @ODM\EmbedOne(targetDocument=Embedded::class)
     *
     * @var Embedded
     */
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
