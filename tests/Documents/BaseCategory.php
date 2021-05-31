<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass(repositoryClass="Documents\BaseCategoryRepository") */
abstract class BaseCategory
{
    /** @ODM\Field(type="string") */
    protected $name;

     /** @ODM\EmbedMany(targetDocument=SubCategory::class) */
    protected $children = [];

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addChild(BaseCategory $child)
    {
        $this->children[] = $child;
    }

    public function getChildren()
    {
        return $this->children;
    }
}
