<?php

declare(strict_types=1);

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Airport
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $code;

    /**
     * @ODM\ReferenceMany(targetDocument=Airport::class, cascade={"persist"}, storeAs="ref")
     *
     * @var Collection<int, Airport>
     */
    protected $connections;

    /**
     * @ODM\ReferenceMany(targetDocument=Airport::class, cascade={"persist"}, storeAs="id")
     *
     * @var Collection<int, Airport>
     */
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
