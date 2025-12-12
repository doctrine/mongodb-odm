<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\QueryResultDocument as QueryResultDocumentAttribute;

/**
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\QueryResultDocument instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class QueryResultDocument extends QueryResultDocumentAttribute implements Annotation
{
}
