<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(collection: 'client')]
class Client
{
    #[ODM\Id]
    public ?string $id;

    public function __construct(
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $name,
        #[ODM\EmbedMany(targetDocument: ClientCard::class)]
        public Collection $clientCards = new ArrayCollection(),
    ) {
    }
}
