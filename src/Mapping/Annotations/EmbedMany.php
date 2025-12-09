<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Embeds multiple documents
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedMany instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EmbedMany extends \Doctrine\ODM\MongoDB\Mapping\Attribute\EmbedMany implements Annotation
{
}
