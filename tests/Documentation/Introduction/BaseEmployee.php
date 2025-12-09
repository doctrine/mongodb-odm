<?php

declare(strict_types=1);

namespace Documentation\Introduction;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\MappedSuperclass]
abstract class BaseEmployee
{
    #[ODM\Field(strategy: 'increment')]
    public int $changes = 0;

    /** @var string[] */
    #[ODM\Field]
    public array $notes = [];

    #[ODM\Field]
    public string $name;

    #[ODM\Field]
    public int $salary;

    #[ODM\Field]
    public DateTimeImmutable $started;

    #[ODM\Field]
    public DateTimeImmutable $left;

    #[ODM\EmbedOne]
    public Address $address;
}
