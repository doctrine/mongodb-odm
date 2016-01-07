<?php

namespace Documents\Ecommerce;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Currency
{
    const
        USD   = 'USD',
        EURO  = 'EURO',
        JPN   = 'JPN';

    /**
     * @ODM\Id
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     */
    protected $name;

    /**
     * @ODM\Field(type="float")
     */
    protected $multiplier;

    public function __construct($name, $multiplier = 1)
    {
        $name = (string) $name;
        if ( ! in_array($name, self::getAll())) {
            throw new \InvalidArgumentException(
                'Currency must be one of ' . implode(', ', self::getAll()) .
                $name . 'given'
            );
        }
        $this->name = $name;
        $this->setMultiplier($multiplier);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMultiplier()
    {
        return $this->multiplier;
    }

    public function setMultiplier($multiplier)
    {
        $multiplier = (float) $multiplier;
        if (empty($multiplier) || $multiplier <= 0) {
            throw new \InvalidArgumentException(
                'currency multiplier must be a positive float number'
            );
        }
        $this->multiplier = $multiplier;
    }

    public static function getAll()
    {
        return array(
            self::USD,
            self::EURO,
            self::JPN,
        );
    }
}
