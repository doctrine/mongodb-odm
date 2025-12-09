<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Event
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var User|null */
    #[ODM\ReferenceOne(targetDocument: User::class)]
    private $user;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $title;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $type;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}
