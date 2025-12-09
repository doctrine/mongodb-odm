<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Embeds a single document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedOne instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EmbedOne extends \Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedOne implements Annotation
{
}
