<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'benchmark_vehicles')]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField('type')]
#[ODM\DiscriminatorMap(['car' => Car::class, 'truck' => Truck::class])]
abstract class Vehicle
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    public string $manufacturer;

    #[ODM\Field(type: 'int')]
    public int $modelYear;
}
