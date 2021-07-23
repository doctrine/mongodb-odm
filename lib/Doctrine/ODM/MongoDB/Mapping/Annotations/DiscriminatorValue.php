<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Use the specified discriminator for this class
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
final class DiscriminatorValue implements Annotation
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
