<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Embeds a single document
 *
 * @Annotation
 */
final class EmbedOne extends AbstractField
{
    /** @var bool */
    public $embedded = true;

    /** @var string|null */
    public $targetDocument;

    /** @var string|null */
    public $discriminatorField;

    /** @var string|null */
    public $discriminatorMap;

    /** @var string|null */
    public $defaultDiscriminatorValue;

    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        ?string $targetDocument = null,
        ?string $discriminatorField = null,
        ?array $discriminatorMap = null,
        ?string $defaultDiscriminatorValue = null
    ) {
        parent::__construct($name, ClassMetadata::ONE, $nullable, $options, $strategy, $notSaved);

        $this->targetDocument = $targetDocument;
        $this->discriminatorField = $discriminatorField;
        $this->discriminatorMap = $discriminatorMap;
        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
    }
}
