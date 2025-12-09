<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use function class_alias;

abstract class AbstractDocument
{
}

// @phpstan-ignore class.notFound
class_alias(AbstractDocument::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractDocument::class);
