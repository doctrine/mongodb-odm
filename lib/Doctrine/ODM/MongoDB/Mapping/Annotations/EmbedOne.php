<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Embeds a single document
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EmbedOne extends AbstractField
{
    /** @var string */
    public $type = ClassMetadata::ONE;

    /** @var bool */
    public $embedded = true;

    /** @var string|null */
    public $targetDocument;

    /** @var string|null */
    public $discriminatorField;

    /** @var array<string, class-string>|null */
    public $discriminatorMap;

    /** @var string|null */
    public $defaultDiscriminatorValue;

    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        string $type = ClassMetadata::ONE,
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
