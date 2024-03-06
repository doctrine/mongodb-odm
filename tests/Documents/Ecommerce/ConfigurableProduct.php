<?php

declare(strict_types=1);

namespace Documents\Ecommerce;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use InvalidArgumentException;

use function array_map;
use function array_search;
use function in_array;

#[ODM\Document]
class ConfigurableProduct
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $name;

    /** @var Collection<int, Option>|array<Option> */
    #[ODM\EmbedMany(targetDocument: Option::class)]
    protected $options = [];

    /** @var Option|null */
    protected $selectedOption;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): ConfigurableProduct
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Product name cannot be empty');
        }

        $this->name = $name;

        return $this;
    }

    /** @return Collection<int, Option>|array<Option> */
    public function getOptions()
    {
        return $this->options;
    }

    /** @param string|Option $name */
    public function addOption($name, ?Money $price = null, ?StockItem $item = null): void
    {
        if (! $name instanceof Option) {
            $name = (string) $name;
            if (empty($name)) {
                throw new InvalidArgumentException('option name cannot be empty');
            }

            $name = new Option($name, $price, $item);
            unset($price, $item);
        }

        if (
            $this->findOption($name->getName()) !== null
            || in_array($name->getStockItem(), $this->getStockItems(), true)
        ) {
            throw new InvalidArgumentException('cannot add option with the same name twice');
        }

        $this->options[] = $name;
    }

    public function getOption(string $name): ?Option
    {
        return $this->findOption($name);
    }

    public function removeOption(string $name): ConfigurableProduct
    {
        $option = $this->findOption($name);
        if ($option === null) {
            throw new InvalidArgumentException('option ' . $name . ' doesn\'t exist');
        }

        if ($this->options instanceof Collection) {
            $index = $this->options->indexOf($option);
        } else {
            $index = array_search($option, $this->options);
        }

        unset($this->options[$index]);

        return $this;
    }

    public function hasOption(string $name): bool
    {
        return $this->findOption($name) !== null;
    }

    public function selectOption(string $name): ConfigurableProduct
    {
        $option = $this->findOption($name);
        if (! isset($option)) {
            throw new InvalidArgumentException('specified option: ' . $name . ' doesn\'t exist');
        }

        $this->selectedOption = $option;

        return $this;
    }

    protected function findOption(string $name): ?Option
    {
        foreach ($this->options as $option) {
            if ($name === $option->getName()) {
                return $option;
            }
        }

        return null;
    }

    /** @return Money|float|null */
    public function getPrice()
    {
        return isset($this->selectedOption) ?
            $this->selectedOption->getPrice() : null;
    }

    /** @return StockItem[] */
    protected function getStockItems(): array
    {
        return array_map(static function ($option) {
            return $option->getStockItem();
        }, $this->getOptions());
    }
}
