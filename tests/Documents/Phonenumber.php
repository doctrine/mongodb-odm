<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Phonenumber
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $phonenumber;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class, cascade={"persist"})
     *
     * @var User|null
     */
    private $lastCalledBy;

    public function __construct(?string $phonenumber = null, ?User $lastCalledBy = null)
    {
        $this->phonenumber  = $phonenumber;
        $this->lastCalledBy = $lastCalledBy;
    }

    public function getPhonenumber(): ?string
    {
        return $this->phonenumber;
    }

    public function setPhonenumber(string $phonenumber): void
    {
        $this->phonenumber = $phonenumber;
    }

    public function getLastCalledBy(): ?User
    {
        return $this->lastCalledBy;
    }

    public function setLastCalledBy(?User $lastCalledBy = null): void
    {
        $this->lastCalledBy = $lastCalledBy;
    }
}
