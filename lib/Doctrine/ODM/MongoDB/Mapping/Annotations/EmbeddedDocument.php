<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Identifies a class as a document that can be embedded but not stored by itself
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EmbeddedDocument extends AbstractDocument
{
    /** @var Index[] */
    public $indexes;

    /** @param Index[] $indexes */
    public function __construct(array $indexes = [])
    {
        $this->indexes = $indexes;
    }
}
