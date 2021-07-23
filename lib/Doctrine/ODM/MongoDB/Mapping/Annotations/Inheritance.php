<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Specifies inheritance mapping for a document
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Inheritance implements Annotation
{
    /** @var string */
    public $type = 'NONE';

    /** @var string[] */
    public $discriminatorMap = [];

    /** @var string|null */
    public $discriminatorField;

    /** @var string|null */
    public $defaultDiscriminatorValue;

    /**
     * @param string[] $discriminatorMap
     */
    public function __construct(
        string $type = 'NONE',
        array $discriminatorMap = [],
        ?string $discriminatorField = null,
        ?string $defaultDiscriminatorValue = null
    ) {
        $this->type                      = $type;
        $this->discriminatorMap          = $discriminatorMap;
        $this->discriminatorField        = $discriminatorField;
        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
    }
}
