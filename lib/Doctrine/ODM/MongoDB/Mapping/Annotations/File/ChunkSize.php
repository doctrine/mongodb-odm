<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;

/**
 * @Annotation
 */
final class ChunkSize extends AbstractField
{
    /** @var string */
    public $name = 'chunkSize';

    /** @var string */
    public $type = 'int';

    /** @var bool */
    public $notSaved = true;
}
