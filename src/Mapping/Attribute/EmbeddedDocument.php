<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Identifies a class as a document that can be embedded but not stored by itself
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class EmbeddedDocument extends AbstractDocument
{
    /** @param Index[] $indexes */
    public function __construct(public readonly array $indexes = [])
    {
    }
}
