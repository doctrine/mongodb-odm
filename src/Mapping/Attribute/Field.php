<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use BackedEnum;

/**
 * Specifies a generic field mapping
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field extends AbstractField implements MappingAttribute
{
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
        public readonly ?string $enumType = null,
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);
    }
}
