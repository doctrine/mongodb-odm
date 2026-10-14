<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Mirrors the embedded "int_doc" shape from the MongoDB driver benchmark
 * corpus: an embedded document with fifteen integer fields.
 */
#[ODM\EmbeddedDocument]
class NestedIntItem
{
    #[ODM\Field(type: 'int')]
    public int $field1;

    #[ODM\Field(type: 'int')]
    public int $field2;

    #[ODM\Field(type: 'int')]
    public int $field3;

    #[ODM\Field(type: 'int')]
    public int $field4;

    #[ODM\Field(type: 'int')]
    public int $field5;

    #[ODM\Field(type: 'int')]
    public int $field6;

    #[ODM\Field(type: 'int')]
    public int $field7;

    #[ODM\Field(type: 'int')]
    public int $field8;

    #[ODM\Field(type: 'int')]
    public int $field9;

    #[ODM\Field(type: 'int')]
    public int $field10;

    #[ODM\Field(type: 'int')]
    public int $field11;

    #[ODM\Field(type: 'int')]
    public int $field12;

    #[ODM\Field(type: 'int')]
    public int $field13;

    #[ODM\Field(type: 'int')]
    public int $field14;

    #[ODM\Field(type: 'int')]
    public int $field15;
}
