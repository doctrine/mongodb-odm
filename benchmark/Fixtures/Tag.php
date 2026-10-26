<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\EmbeddedDocument]
class Tag
{
    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\EmbedOne(targetDocument: TagMeta::class)]
    public ?TagMeta $meta = null;
}
