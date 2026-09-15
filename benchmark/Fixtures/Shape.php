<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Fixtures;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
#[ODM\DiscriminatorField('type')]
#[ODM\DiscriminatorMap(['circle' => Circle::class, 'square' => Square::class])]
abstract class Shape
{
}
