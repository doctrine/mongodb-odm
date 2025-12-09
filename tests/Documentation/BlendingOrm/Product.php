<?php

declare(strict_types=1);

namespace Documentation\BlendingOrm;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

#[Document]
class Product
{
    #[Id]
    public string $id;

    #[Field]
    public string $title;
}
