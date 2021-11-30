<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Loads data from a different field if the original field is not set
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class AlsoLoad implements Annotation
{
    /** @var string|string[] */
    public $value;

    /** @var string|null */
    public $name;

    /**
     * @param string|string[] $value
     */
    public function __construct($value, ?string $name = null)
    {
        $this->value = $value;
        $this->name  = $name;
    }
}
