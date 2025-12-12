<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedOne as EmbedOneAttribute;

/**
 * Embeds a single document
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedOne instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EmbedOne extends EmbedOneAttribute implements Annotation
{
}
