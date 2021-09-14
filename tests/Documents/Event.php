<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Event
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\ReferenceOne(targetDocument=User::class)
     *
     * @var User|null
     */
    private $user;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $title;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
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

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }
}
