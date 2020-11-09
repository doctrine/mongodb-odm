<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;

/**
 * @Annotation
 */
final class ChunkSize extends AbstractField
{
    public function __construct(
        string $name = 'chunkSize',
        string $type = 'int',
        bool $nullable = false,
        array $options = [],
        ?string $strategy = null,
        bool $notSaved = true
    ) {
        parent::__construct($name, $type, $nullable, $options, $strategy, $notSaved);
    }
}
