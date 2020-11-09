<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

/**
 * Special field mapping to map document identifiers
 *
 * @Annotation
 */
final class Id extends AbstractField
{
    /** @var bool */
    public $id = true;

    public function __construct(
        ?string $name = null,
        ?string $type = null,
        bool $nullable = false,
        array $options = [],
        string $strategy = 'auto',
        bool $notSaved = false
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);
    }
}
