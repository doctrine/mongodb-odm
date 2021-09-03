<?php

declare(strict_types=1);

namespace Documents\Ecommerce;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class StockItem
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
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    private $inventory;

    /**
     * @ODM\EmbedOne(targetDocument=Documents\Ecommerce\Money::class)
     *
     * @var Money|null
     */
    private $cost;

    public function getId()
    {
        return $this->id;
    }

    public function __construct($name = null, $cost = null, $inventory = null)
    {
        if ($name !== null) {
            $this->setName($name);
        }

        if ($cost !== null) {
            $this->setCost($cost);
        }

        if ($inventory === null) {
            return;
        }

        $this->setInventory($inventory);
    }

    public function setName($name): StockItem
    {
        $this->name = (string) $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCost(Money $cost): void
    {
        $this->cost = $cost;
    }

    public function getCost()
    {
        return $this->cost->getAmount();
    }

    public function setInventory($inventory): StockItem
    {
        $this->inventory = (int) $inventory;

        return $this;
    }

    public function getInventory()
    {
        return $this->inventory;
    }
}
