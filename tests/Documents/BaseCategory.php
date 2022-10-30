<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass(repositoryClass="Documents\BaseCategoryRepository") */
abstract class BaseCategory
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $name;

     /**
      * @ODM\EmbedMany(targetDocument=SubCategory::class)
      *
      * @var Collection<int, SubCategory>|array<SubCategory>
      */
    protected $children = [];

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addChild(BaseCategory $child): void
    {
        $this->children[] = $child;
    }

    /** @return Collection<int, SubCategory>|array<SubCategory> */
    public function getChildren()
    {
        return $this->children;
    }
}
