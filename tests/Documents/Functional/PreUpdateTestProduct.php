<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="pre_update_test_product") */
class PreUpdateTestProduct
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
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedOne(targetDocument=PreUpdateTestSellable::class)
     *
     * @var PreUpdateTestSellable|null
     */
    public $sellable;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
