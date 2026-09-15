<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Car extends Vehicle
{
    #[ODM\Field(type: 'int')]
    public int $numberOfDoors;
}
