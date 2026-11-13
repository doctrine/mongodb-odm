<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'polymorphic_contact')]
class PolymorphicContact
{
    #[ODM\Id]
    public ?string $id;

    public function __construct(
        #[ODM\EmbedOne(discriminatorField: 'type', discriminatorMap: ['phone' => PhoneNumber::class, 'email' => EmailAddress::class])]
        public object $contact,
    ) {
    }
}
