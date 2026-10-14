<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Mirrors the shape of the "small_doc" fixture from the MongoDB driver
 * benchmark corpus: a wide, flat document with string and integer fields.
 */
#[ODM\Document(collection: 'benchmark_flat_documents')]
class FlatDocument
{
    #[ODM\Id]
    public ?string $id = null;

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
}
