<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * Embeds multiple documents
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class EmbedMany extends AbstractField
{
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

    /** @var string|null */
    public $collectionClass;

    /** @var bool */
    public $storeEmptyArray;

    /** @param array<string, class-string>|null $discriminatorMap */
    public function __construct(
        ?string $name = null,
        bool $nullable = false,
        array $options = [],
        string $strategy = CollectionHelper::DEFAULT_STRATEGY,
        bool $notSaved = false,
        ?string $targetDocument = null,
        ?string $discriminatorField = null,
        ?array $discriminatorMap = null,
        ?string $defaultDiscriminatorValue = null,
        ?string $collectionClass = null,
        bool $storeEmptyArray = false,
    ) {
        parent::__construct($name, ClassMetadata::MANY, $nullable, $options, $strategy, $notSaved);

        $this->targetDocument            = $targetDocument;
        $this->discriminatorField        = $discriminatorField;
        $this->discriminatorMap          = $discriminatorMap;
        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
        $this->collectionClass           = $collectionClass;
        $this->storeEmptyArray           = $storeEmptyArray;
    }
}
