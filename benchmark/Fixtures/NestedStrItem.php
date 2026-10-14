<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Mirrors the embedded "str_doc" shape from the MongoDB driver benchmark
 * corpus: an embedded document with fifteen string fields.
 */
#[ODM\EmbeddedDocument]
class NestedStrItem
{
    #[ODM\Field(type: 'string')]
    public string $field1;

    #[ODM\Field(type: 'string')]
    public string $field2;

    #[ODM\Field(type: 'string')]
    public string $field3;

    #[ODM\Field(type: 'string')]
    public string $field4;

    #[ODM\Field(type: 'string')]
    public string $field5;

    #[ODM\Field(type: 'string')]
    public string $field6;

    #[ODM\Field(type: 'string')]
    public string $field7;

    #[ODM\Field(type: 'string')]
    public string $field8;

    #[ODM\Field(type: 'string')]
    public string $field9;

    #[ODM\Field(type: 'string')]
    public string $field10;

    #[ODM\Field(type: 'string')]
    public string $field11;

    #[ODM\Field(type: 'string')]
    public string $field12;

    #[ODM\Field(type: 'string')]
    public string $field13;

    #[ODM\Field(type: 'string')]
    public string $field14;

    #[ODM\Field(type: 'string')]
    public string $field15;
}
