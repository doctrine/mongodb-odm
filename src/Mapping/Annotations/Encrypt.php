<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Encrypt as EncryptAttribute;

/**
 * Defines an encrypted field mapping.
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Encrypt instead
 *
 * @see https://www.mongodb.com/docs/manual/core/queryable-encryption/fundamentals/encrypt-and-query/#configure-encrypted-fields-for-optimal-search-and-storage
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Encrypt extends EncryptAttribute implements Annotation
{
}
