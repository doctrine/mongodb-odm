<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Metadata extends AbstractField
{
    /** @var string */
    public $name = 'metadata';

    /** @var string */
    public $type = ClassMetadata::ONE;

    /** @var bool */
    public $embedded = true;

    /** @var string|null */
    public $targetDocument;

    /** @var string|null */
    public $discriminatorField;

    /** @var array|null */
    public $discriminatorMap;

    /** @var string|null */
    public $defaultDiscriminatorValue;

    public function __construct(
        ?string $name = 'metadata',
        string $type = ClassMetadata::ONE,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        bool $embedded = true,
        ?string $targetDocument = null,
        ?string $discriminatorField = null,
        ?array $discriminatorMap = null,
        ?string $defaultDiscriminatorValue = null
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);

        $this->embedded                  = $embedded;
        $this->targetDocument            = $targetDocument;
        $this->discriminatorField        = $discriminatorField;
        $this->discriminatorMap          = $discriminatorMap;
        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
    }
}
