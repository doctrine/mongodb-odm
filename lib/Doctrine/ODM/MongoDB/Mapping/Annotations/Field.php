<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use BackedEnum;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Specifies a generic field mapping
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field extends AbstractField
{
    /** @var class-string<BackedEnum>|null */
    public $enumType;

    /**
     * @param mixed[]                       $options
     * @param class-string<BackedEnum>|null $enumType
     */
    public function __construct(
        ?string $name = null,
        ?string $type = null,
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = false,
        ?string $enumType = null,
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);

        $this->enumType = $enumType;
    }
}
