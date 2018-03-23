<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations\File;

use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractField;

/**
 * @Annotation
 */
final class UploadDate extends AbstractField
{
    /** @var string */
    public $name = 'uploadDate';

    /** @var string */
    public $type = 'date';

    /** @var bool */
    public $notSaved = true;
}
