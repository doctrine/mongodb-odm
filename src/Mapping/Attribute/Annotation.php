<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use function class_alias;

interface Annotation
{
}

// @phpstan-ignore class.notFound
class_alias(Annotation::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\Annotation::class);
