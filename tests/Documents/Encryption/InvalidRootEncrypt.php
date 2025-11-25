<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Encrypt;

/** Root document cannot be encrypted. */
#[Document]
#[Encrypt]
class InvalidRootEncrypt
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field]
    public string $name;
}
