<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Encrypt;

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
