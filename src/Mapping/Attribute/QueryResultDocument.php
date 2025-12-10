<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/** @final */
#[Attribute(Attribute::TARGET_CLASS)]
class QueryResultDocument extends AbstractDocument
{
}
