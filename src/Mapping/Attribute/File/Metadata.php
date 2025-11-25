<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute\File;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractField;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

use function class_alias;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Metadata extends AbstractField
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

    /** @param array<string, class-string>|null $discriminatorMap */
    public function __construct(
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        ?string $targetDocument = null,
        ?string $discriminatorField = null,
        ?array $discriminatorMap = null,
        ?string $defaultDiscriminatorValue = null,
    ) {
        parent::__construct('metadata', ClassMetadata::ONE, $nullable, $options, $strategy, $notSaved);

        $this->targetDocument            = $targetDocument;
        $this->discriminatorField        = $discriminatorField;
        $this->discriminatorMap          = $discriminatorMap;
        $this->defaultDiscriminatorValue = $defaultDiscriminatorValue;
    }
}

// @phpstan-ignore class.notFound
class_alias(Metadata::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\File\Metadata::class);
