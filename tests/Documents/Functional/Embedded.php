<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Embedded
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}
