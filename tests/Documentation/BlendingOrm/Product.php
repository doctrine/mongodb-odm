<?php

declare(strict_types=1);

namespace Documentation\BlendingOrm;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;

#[Document]
class Product
{
    #[Id]
    public string $id;

    #[Field]
    public string $title;
}
