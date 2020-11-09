<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/** @Annotation */
final class ShardKey implements NamedArgumentConstructorAnnotation
{
    /** @var string[] */
    public $keys = [];

    /** @var bool|null */
    public $unique;

    /** @var int|null */
    public $numInitialChunks;

    public function __construct(
        array $keys = [],
        ?bool $unique = null,
        ?int $numInitialChunks = null
    ) {
        $this->keys = $keys;
        $this->unique = $unique;
        $this->numInitialChunks = $numInitialChunks;
    }
}
