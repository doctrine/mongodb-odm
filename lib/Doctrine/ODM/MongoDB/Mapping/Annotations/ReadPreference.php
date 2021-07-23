<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ReadPreference implements Annotation
{
    /** @var string|null */
    public $value;
    /** @var string[][]|null */
    public $tags;

    public function __construct(?string $value = null, ?array $tags = null)
    {
        $this->value = $value;
        $this->tags  = $tags;
    }
}
