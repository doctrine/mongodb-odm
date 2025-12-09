<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;

/** @final */
#[Attribute(Attribute::TARGET_CLASS)]
class ShardKey implements MappingAttribute
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
