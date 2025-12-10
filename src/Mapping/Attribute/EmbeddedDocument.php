<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/**
 * Identifies a class as a document that can be embedded but not stored by itself
 *
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class EmbeddedDocument extends AbstractDocument
{
    /** @var Index[] */
    public $indexes;

    /** @param Index[] $indexes */
    public function __construct(array $indexes = [])
    {
        $this->indexes = $indexes;
    }
}
