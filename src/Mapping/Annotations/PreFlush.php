<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

/**
 * Marks a method as a preFlush lifecycle callback
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class PreFlush implements Annotation
{
}
