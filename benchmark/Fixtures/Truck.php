<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Truck extends Vehicle
{
    #[ODM\Field(type: 'float')]
    public float $payloadCapacity;
}
