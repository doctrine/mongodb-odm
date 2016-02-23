<?php

namespace Documents\Ecommerce;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument()
 */
class Option
{
    /** @ODM\Id */
    protected $id;

    /**
     * @ODM\Field(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ODM\EmbedOne(targetDocument="Documents\Ecommerce\Money")
     * @var float
     */
    protected $money;

    /**
     * @ODM\ReferenceOne(targetDocument="Documents\Ecommerce\StockItem", cascade="all")
     * @var Documents\StockItem
     */
    protected $stockItem;

    /**
     * @param string $name
     * @param float $price
     * @param StockItem $stockItem
     */
    public function __construct($name, Money $money, StockItem $stockItem)
    {
        $this->name = (string) $name;
        if (empty($this->name)) {
            throw new \InvalidArgumentException('option name cannot be empty');
        }
        $this->money = $money;
        if (empty($this->money)) {
            throw new \InvalidArgumentException('option price cannot be empty');
        }
        $this->stockItem = $stockItem;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getPrice($object = false)
    {
        if (true === $object) {
            return $this->money;
        }
        return $this->money->getAmount();
    }

    /**
     * @return StockItem
     */
    public function getStockItem()
    {
        return $this->stockItem;
    }
}
