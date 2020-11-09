<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\NamedArgumentConstructorAnnotation;

/**
 * @Annotation
 */
final class ReadPreference implements NamedArgumentConstructorAnnotation
{
    /** @var string */
    public $value;

    /** @var string[][]|null */
    public $tags;

    public function __construct(string $value, array $tags = [])
    {
        $this->value = $value;
        $this->tags = $tags;
    }
}
