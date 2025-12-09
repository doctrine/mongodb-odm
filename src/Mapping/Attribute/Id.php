<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

use function class_alias;

/**
 * Special field mapping to map document identifiers
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Id extends AbstractField
{
    /** @var bool */
    public $id = true;

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = 'auto',
        bool $notSaved = false,
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);
    }
}

// @phpstan-ignore class.notFound
class_alias(Id::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\Id::class);
