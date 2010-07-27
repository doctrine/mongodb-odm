<?php

namespace Documents\Functional;

/** @Document(collection="favorites_user") */
class FavoritesUser
{
    /** @Id */
    private $id;

    /** @String */
    private $name;

    /**
     * @ReferenceMany(
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "group"="Documents\Group",
     *     "project"="Documents\Project"
     *   }
     * )
     */
    private $favorites = array();

    /** @EmbedMany */
    private $embedded = array();

    /** @ReferenceOne */
    private $favorite;

    /** @EmbedOne */
    private $embed;

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