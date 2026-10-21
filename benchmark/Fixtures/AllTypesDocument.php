<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Exercises a broad set of built-in ODM types in a single document, to
 * catch regressions in Type conversion that a narrow set of fields
 * would not surface.
 */
#[ODM\Document(collection: 'benchmark_all_types_documents')]
class AllTypesDocument
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    public string $name;

    #[ODM\Field(type: 'int')]
    public int $intValue;

    #[ODM\Field(type: 'float')]
    public float $floatValue;

    #[ODM\Field(type: 'bool')]
    public bool $boolValue;

    #[ODM\Field(type: 'date_immutable')]
    public DateTimeImmutable $dateValue;

    #[ODM\Field(type: 'int', strategy: 'increment')]
    public int $counter = 0;

    /** @var list<string> */
    #[ODM\Field(type: 'collection')]
    public array $tags = [];
}
