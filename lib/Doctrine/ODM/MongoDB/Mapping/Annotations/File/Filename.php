<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;

/**
 * @Annotation
 */
final class Filename extends AbstractField
{
    /** @var string */
    public $name = 'filename';

    /** @var string */
    public $type = 'string';

    /** @var bool */
    public $notSaved = true;
}
