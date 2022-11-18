<?php

declare(strict_types=1);

namespace Documents81;

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

    public ?SuitNonBacked $suitNonBacked;
}
