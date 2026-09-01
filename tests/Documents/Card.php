<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document]
class Card
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field()]
    public Suit $suit;

    #[ODM\Field()]
    public ?SuitInt $suitInt;

    #[ODM\Field(type: 'string', enumType: Suit::class, nullable: true)]
    public ?Suit $nullableSuit;

    /** @var Suit[] */
    #[ODM\Field(enumType: Suit::class)]
    public array $suits;

    public ?SuitNonBacked $suitNonBacked;
}
