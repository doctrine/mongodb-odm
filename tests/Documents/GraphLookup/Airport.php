<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Airport
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $code;

    /** @var Collection<int, Airport> */
    #[ODM\ReferenceMany(targetDocument: self::class, cascade: ['persist'], storeAs: 'ref')]
    protected $connections;

    /** @var Collection<int, Airport> */
    #[ODM\ReferenceMany(targetDocument: self::class, cascade: ['persist'], storeAs: 'id')]
    protected $connectionIds;

    public function __construct(string $code)
    {
        $this->code          = $code;
        $this->connections   = new ArrayCollection();
        $this->connectionIds = new ArrayCollection();
    }

    public function addConnection(Airport $airport): void
    {
        if ($this->connections->contains($airport)) {
            return;
        }

        $this->connections->add($airport);
        $this->connectionIds->add($airport);
        $airport->addConnection($this);
    }
}
