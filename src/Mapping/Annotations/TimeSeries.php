<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Marks a document or superclass as a time series document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\TimeSeries instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TimeSeries extends \Doctrine\ODM\MongoDB\Mapping\Attribute\TimeSeries implements Annotation
{
}
