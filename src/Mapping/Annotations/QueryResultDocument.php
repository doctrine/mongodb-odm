<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class QueryResultDocument extends AbstractDocument
{
}
