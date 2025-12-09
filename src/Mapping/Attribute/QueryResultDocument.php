<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class QueryResultDocument extends AbstractDocument
{
}

// @phpstan-ignore class.notFound
class_alias(QueryResultDocument::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\QueryResultDocument::class);
