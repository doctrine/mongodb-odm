<?php

declare(strict_types=1);

namespace Documents74;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document() */
class GH2349Customer
{
    public const ID = '610419a277d50f7609139542';

    /** @ODM\Id() */
    private string $id;

    /** @ODM\Field() */
    private string $name;

    private array $domainEvents = [];

    public function __construct(string $name)
    {
        $this->id   = self::ID;
        $this->name = $name;
    }

    public function doSomeUpdate(): void
    {
        $this->domainEvents[] = 'a new event!';
    }

    public function getEvents(): array
    {
        return $this->domainEvents;
    }
}
