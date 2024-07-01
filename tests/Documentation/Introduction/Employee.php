<?php

declare(strict_types=1);

namespace Documentation\Introduction;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Employee extends BaseEmployee
{
    #[ODM\Id]
    public string $id;

    #[ODM\ReferenceOne(targetDocument: Manager::class)]
    public ?Manager $manager = null;
}
