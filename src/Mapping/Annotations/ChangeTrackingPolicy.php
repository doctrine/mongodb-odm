<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

/**
 * Specifies the change tracking policy for a document
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ChangeTrackingPolicy implements Annotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
