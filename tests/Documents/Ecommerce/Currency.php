<?php

declare(strict_types=1);

namespace Documents\Ecommerce;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use InvalidArgumentException;

use function implode;
use function in_array;

/** @ODM\Document */
class Currency
{
    public const USD  = 'USD';
    public const EURO = 'EURO';
    public const JPN  = 'JPN';

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ODM\Field(type="float")
     *
     * @var float
     */
    protected $multiplier;

    /** @param float|int $multiplier */
    public function __construct(string $name, $multiplier = 1)
    {
        if (! in_array($name, self::getAll())) {
            throw new InvalidArgumentException(
                'Currency must be one of ' . implode(', ', self::getAll()) .
                $name . 'given',
            );
        }

        $this->name = $name;
        $this->setMultiplier($multiplier);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMultiplier(): float
    {
        return $this->multiplier;
    }

    /** @param float|int|string $multiplier */
    public function setMultiplier($multiplier): void
    {
        $multiplier = (float) $multiplier;
        if (empty($multiplier) || $multiplier <= 0) {
            throw new InvalidArgumentException(
                'currency multiplier must be a positive float number',
            );
        }

        $this->multiplier = $multiplier;
    }

    /** @return string[] */
    public static function getAll(): array
    {
        return [
            self::USD,
            self::EURO,
            self::JPN,
        ];
    }
}
