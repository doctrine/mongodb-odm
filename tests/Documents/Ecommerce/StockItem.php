<?php

declare(strict_types=1);

namespace Documents\Ecommerce;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class StockItem
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $name;

    /** @var int|null */
    #[ODM\Field(type: 'int')]
    private $inventory;

    /** @var Money|null */
    #[ODM\EmbedOne(targetDocument: Money::class)]
    private $cost;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function __construct(?string $name = null, ?Money $cost = null, ?int $inventory = null)
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

    public function setName(string $name): StockItem
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setCost(Money $cost): void
    {
        $this->cost = $cost;
    }

    public function getCost(): float
    {
        return $this->cost->getAmount();
    }

    public function setInventory(int $inventory): StockItem
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function getInventory(): ?int
    {
        return $this->inventory;
    }
}
