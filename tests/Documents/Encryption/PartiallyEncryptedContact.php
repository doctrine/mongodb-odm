<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'partially_encrypted_contact')]
class PartiallyEncryptedContact
{
    #[ODM\Id]
    public ?string $id;

    public function __construct(
        #[ODM\EmbedOne(discriminatorField: 'type', discriminatorMap: ['card' => ClientCard::class, 'phone' => PhoneNumber::class])]
        public object $contact,
    ) {
    }
}
