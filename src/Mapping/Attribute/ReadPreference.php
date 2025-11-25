<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class ReadPreference implements Annotation
{
    /** @var string */
    public $value;

    /** @var string[][]|null */
    public $tags;

    /** @param string[][]|null $tags */
    public function __construct(string $value, ?array $tags = null)
    {
        $this->value = $value;
        $this->tags  = $tags;
    }
}

// @phpstan-ignore class.notFound
class_alias(ReadPreference::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\ReadPreference::class);
