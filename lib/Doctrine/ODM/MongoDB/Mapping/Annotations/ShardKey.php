<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
final class ShardKey extends Annotation
{
    /** @var string[] */
    public $keys = [];

    /** @var bool|null */
    public $unique;

    /** @var int|null */
    public $numInitialChunks;
}
