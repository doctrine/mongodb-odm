<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;

/**
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Length extends AbstractField
{
    /** @var string */
    public $name = 'length';

    /** @var string */
    public $type = 'int';

    /** @var bool */
    public $notSaved = true;

    public function __construct(
        ?string $name = 'length',
        string $type = 'int',
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = true
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);
    }
}
