<?php

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
final class ShardKey extends Annotation
{
    public $keys = array();
    public $unique;
    public $numInitialChunks;
}
