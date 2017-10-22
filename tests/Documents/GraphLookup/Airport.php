<?php

namespace Documents\GraphLookup;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Airport
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $code;

    /**
     * @ODM\ReferenceMany(targetDocument=Airport::class, cascade={"persist"}, storeAs="ref")
     */
    protected $connections;

    /**
     * @ODM\ReferenceMany(targetDocument=Airport::class, cascade={"persist"}, storeAs="id")
     */
    protected $connectionIds;

    public function __construct($code)
    {
        $this->code = $code;
        $this->connections = new ArrayCollection();
        $this->connectionIds = new ArrayCollection();
    }

    public function addConnection(Airport $airport)
    {
        if ($this->connections->contains($airport)) {
            return;
        }

        $this->connections->add($airport);
        $this->connectionIds->add($airport);
        $airport->addConnection($this);
    }
}
