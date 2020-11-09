<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * Identifies a class as a document that can be embedded but not stored by itself
 *
 * @Annotation
 */
final class EmbeddedDocument extends AbstractDocument implements NamedArgumentConstructorAnnotation
{
    /** @var Index[] */
    public $indexes;

    public function __construct(array $indexes = [])
    {
        $this->indexes = $indexes;
    }
}
