<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'schemaless_encrypted_contact')]
class SchemalessEncryptedContact
{
    #[ODM\Id]
    public ?string $id;

    /** @var list<object> */
    #[ODM\EmbedMany]
    #[ODM\Encrypt]
    public array $details = [];
}
