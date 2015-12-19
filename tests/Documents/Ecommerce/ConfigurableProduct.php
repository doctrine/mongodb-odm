<?php

namespace Documents\Ecommerce;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class ConfigurableProduct
{
    /**
     * @ODM\Id
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     */
    protected $name;

    /**
     * @ODM\EmbedMany(targetDocument="Documents\Ecommerce\Option")
     */
    protected $options = array();

    /**
     * @var Documents\Option
     */
    protected $selectedOption;

    public function  __construct($name)
    {
        $this->setName($name);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $name = (string) $name;
        if (empty($name)) {
            throw new \InvalidArgumentException('Product name cannot be empty');
        }
        $this->name = $name;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string|Option $name
     * @param float|null $price
     * @param StockItem|null $item
     */
    public function addOption($name, $price = null, StockItem $item = null)
    {
        if ( ! $name instanceof Option) {
            $name = (string) $name;
            if (empty($name)) {
                throw new \InvalidArgumentException('option name cannot be empty');
            }
            $name = new Option($name, $price, $item);
            unset($price, $item);
        }
        if (null !== $this->_findOption($name->getName())
            || in_array($name->getStockItem(), $this->_getStockItems(), true)) {
            throw new \InvalidArgumentException('cannot add option with the same name twice');
        }
        $this->options[] = $name;
    }

    public function getOption($name)
    {
        return $this->_findOption($name);
    }

    public function removeOption($name)
    {
        if(null === ($option = $this->_findOption($name))) {
            throw new \InvalidArgumentException('option ' . $name . ' doesn\'t exist');
        }
        if ($this->options instanceof \Doctrine\Common\Collections\Collection) {
            $index = $this->options->indexOf($option);
        } else {
            $index = array_search($option, $this->options);
        }
        unset($this->options[$index]);
        return $this;
    }

    public function hasOption($name)
    {
        return null !== $this->_findOption($name);
    }

    public function selectOption($name)
    {
        $option = $this->_findOption($name);
        if ( ! isset($option)) {
            throw new \InvalidArgumentException('specified option: ' . $name . ' doesn\'t exist');
        }
        $this->selectedOption = $option;
        return $this;
    }

    protected function _findOption($name)
    {
        foreach ($this->options as $option) {
            if ($name == $option->getName()) {
                return $option;
            }
        }
        return null;
    }

    public function getPrice()
    {
        return isset($this->selectedOption) ?
            $this->selectedOption->getPrice() : null;
    }

    protected function _getStockItems()
    {
        $stockItems = array();
        foreach ($this->getOptions() as $option) {
            $stockItems[] = $option->getStockItem();
        }
        return $stockItems;
    }

}
