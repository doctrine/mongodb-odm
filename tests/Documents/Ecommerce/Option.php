<?php

declare(strict_types=1);

namespace Documents\Ecommerce;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use InvalidArgumentException;

/**
 * @ODM\EmbeddedDocument()
 */
class Option
{
    /** @ODM\Id */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ODM\EmbedOne(targetDocument=Documents\Ecommerce\Money::class)
     *
     * @var Money
     */
    protected $money;

    /**
     * @ODM\ReferenceOne(targetDocument=Documents\Ecommerce\StockItem::class, cascade="all")
     *
     * @var StockItem
     */
    protected $stockItem;

    public function __construct(string $name, Money $money, StockItem $stockItem)
    {
        $this->name = (string) $name;
        if (empty($this->name)) {
            throw new InvalidArgumentException('option name cannot be empty');
        }

        $this->money = $money;
        if (empty($this->money)) {
            throw new InvalidArgumentException('option price cannot be empty');
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
     * @return float|Money
     */
    public function getPrice($object = false)
    {
        if ($object === true) {
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
