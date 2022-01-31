<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Feature
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
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=Product::class, inversedBy="features", cascade={"all"})
     *
     * @var Product|null
     */
    public $product;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
