<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
#[ODM\Document]
class Card
{
    /** @ODM\Id */
    #[ODM\Id]
    public string $id;

    /** @ODM\Field() */
    #[ODM\Field()]
    public Suit $suit;

    /** @ODM\Field() */
    #[ODM\Field()]
    public ?SuitInt $suitInt;

    /** @ODM\Field(type="string", enumType=Suit::class, nullable=true) */
    #[ODM\Field(type: 'string', enumType: Suit::class, nullable: true)]
    public ?Suit $nullableSuit;

    /**
     * @ODM\Field(enumType=Suit::class)
     *
     * @var Suit[]
     */
    #[ODM\Field(enumType: Suit::class)]
    public array $suits;

    public ?SuitNonBacked $suitNonBacked;
}
