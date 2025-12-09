<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Identifies a class as a GridFS file that can be stored in the database
 *
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\File instead
 *
 * @Annotation
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class File extends \Doctrine\ODM\MongoDB\Mapping\Attribute\File implements Annotation
{
}
