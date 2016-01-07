<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="favorites_user") */
class FavoritesUser
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /**
     * @ODM\ReferenceMany(
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "group"="Documents\Group",
     *     "project"="Documents\Project"
     *   }
     * )
     */
    private $favorites = array();

    /** @ODM\EmbedMany */
    private $embedded = array();

    /** @ODM\ReferenceOne */
    private $favorite;

    /** @ODM\EmbedOne */
    private $embed;

    public function getId()
    {
        return $this->id;
    }

    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;
    }

    public function getFavorite()
    {
        return $this->favorite;
    }

    public function setEmbed($embed)
    {
        $this->embed = $embed;
    }

    public function getEmbed()
    {
        return $this->embed;
    }

    public function embed($document)
    {
        $this->embedded[] = $document;
    }

    public function getEmbedded()
    {
        return $this->embedded;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function addFavorite($favorite)
    {
        $this->favorites[] = $favorite;
    }

    public function getFavorites()
    {
        return $this->favorites;
    }
}
