<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Feature
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Product|null */
    #[ODM\ReferenceOne(targetDocument: Product::class, inversedBy: 'features', cascade: ['all'])]
    public $product;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
