<?php

declare(strict_types=1);

namespace Documents81;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
#[ODM\Document]
class Card
{
    /** @ODM\Id */
    #[ODM\Id]
    public string $id;

    /** @ODM\Field(type="string", enumType=Suit::class) */
    #[ODM\Field(type: 'string', enumType: Suit::class)]
    public Suit $suit;

    /** @ODM\Field(type="string", enumType=Suit::class, nullable=true) */
    #[ODM\Field(type: 'string', enumType: Suit::class, nullable: true)]
    public ?Suit $nullableSuit;
}
