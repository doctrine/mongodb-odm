<?php

declare(strict_types=1);

namespace Documents74;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 */
class TypedDocument
{
    /**
     * @ODM\Id()
     */
    private string $id;

    /**
     * @ODM\Field(type="string")
     */
    private string $name;

    /**
     * @ODM\EmbedOne(targetDocument=TypedEmbeddedDocument::class)
     */
    private TypedEmbeddedDocument $embedOne;

    /**
     * @ODM\EmbedMany(targetDocument=TypedEmbeddedDocument::class)
     */
    private Collection $embedMany;

    public function __construct()
    {
        $this->embedMany = new ArrayCollection();
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function setId(string $id) : void
    {
        $this->id = $id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getEmbedOne() : TypedEmbeddedDocument
    {
        return $this->embedOne;
    }

    public function setEmbedOne(TypedEmbeddedDocument $embedOne) : void
    {
        $this->embedOne = $embedOne;
    }

    public function getEmbedMany() : Collection
    {
        return $this->embedMany;
    }
}
