<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'schemaless_contact')]
class SchemalessContact
{
    #[ODM\Id]
    public ?string $id;

    /** @var list<object> */
    #[ODM\EmbedMany]
    public array $details = [];
}
