<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Address
{
    #[ODM\Field(type: 'string')]
    public string $street;

    #[ODM\Field(type: 'string')]
    public string $city;

    #[ODM\Field(type: 'string')]
    public string $zipCode;
}
