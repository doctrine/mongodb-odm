<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use Attribute;
use BackedEnum;

/**
 * Specifies a generic field mapping
 *
 * @final
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Field extends AbstractField implements MappingAttribute
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
