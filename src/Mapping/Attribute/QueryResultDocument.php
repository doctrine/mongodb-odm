<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class QueryResultDocument extends AbstractDocument
{
}
