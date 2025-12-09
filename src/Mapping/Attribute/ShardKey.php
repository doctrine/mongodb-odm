<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ShardKey implements Annotation
{
    /** @var string[] */
    public $keys;

    /** @var bool|null */
    public $unique;

    /** @var int|null */
    public $numInitialChunks;

    /** @param string[] $keys */
    public function __construct(array $keys = [], ?bool $unique = null, ?int $numInitialChunks = null)
    {
        $this->keys             = $keys;
        $this->unique           = $unique;
        $this->numInitialChunks = $numInitialChunks;
    }
}

// @phpstan-ignore class.notFound
class_alias(ShardKey::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\ShardKey::class);
