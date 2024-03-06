<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Phonenumber
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $phonenumber;

    /** @var User|null */
    #[ODM\ReferenceOne(targetDocument: User::class, cascade: ['persist'])]
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
