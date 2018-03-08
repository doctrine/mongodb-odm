<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
final class ShardKey extends Annotation
{
    public $keys = [];
    public $unique;
    public $numInitialChunks;
}
