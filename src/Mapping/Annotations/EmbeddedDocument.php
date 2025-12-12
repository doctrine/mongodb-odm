<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\EmbeddedDocument as EmbeddedDocumentAttribute;

/**
 * Identifies a class as a document that can be embedded but not stored by itself
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\EmbeddedDocument instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EmbeddedDocument extends EmbeddedDocumentAttribute implements Annotation
{
}
