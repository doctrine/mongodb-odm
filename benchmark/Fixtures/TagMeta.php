<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class TagMeta
{
    #[ODM\Field(type: 'int')]
    public int $weight;

    #[ODM\Field(type: 'string')]
    public string $note;
}
