<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Identifies a class as a document that can be embedded but not stored by itself
 *
 * @Annotation
 */
final class EmbeddedDocument extends AbstractDocument
{
    /** @var Index[] */
    public $indexes = [];
}
