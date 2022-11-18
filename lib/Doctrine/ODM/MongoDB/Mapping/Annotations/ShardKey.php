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
final class ShardKey implements Annotation
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
