<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Group;
use Documents\Project;

/** @ODM\Document(collection="favorites_user") */
class FavoritesUser
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    /**
     * @ODM\ReferenceMany(
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "group"=Documents\Group::class,
     *     "project"=Documents\Project::class
     *   }
     * )
     *
     * @var Collection<int, Group|Project>
     */
    private $favorites;

    /**
     * @ODM\EmbedMany
     *
     * @var Collection<int, object>|array<object>
     */
    private $embedded = [];

    /**
     * @ODM\ReferenceOne
     *
     * @var object|null
     */
    private $favorite;

    /**
     * @ODM\EmbedOne
     *
     * @var object|null
     */
    private $embed;

    public function __construct()
    {
        $this->favorites = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setFavorite(object $favorite): void
    {
        $this->favorite = $favorite;
    }

    public function getFavorite(): ?object
    {
        return $this->favorite;
    }

    public function setEmbed(object $embed): void
    {
        $this->embed = $embed;
    }

    public function getEmbed(): ?object
    {
        return $this->embed;
    }

    public function embed(object $document): void
    {
        $this->embedded[] = $document;
    }

    /** @return Collection<int, object>|array<object> */
    public function getEmbedded()
    {
        return $this->embedded;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @param Group|Project $favorite */
    public function addFavorite($favorite): void
    {
        $this->favorites[] = $favorite;
    }

    /** @return Collection<int, Group|Project> */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }
}
