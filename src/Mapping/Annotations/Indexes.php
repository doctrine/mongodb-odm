<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Attribute;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\Indexes::class);

return;

/**
 * Specifies a list of indexes for a document
 *
 * @deprecated final class was deprecated in doctrine/mongodb-odm 2.2 and will be removed in 3.0. Specify all Index and UniqueIndex annotations on a final class level.
 * @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\Indexes instead
 *
 * @Annotation
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Indexes extends \Doctrine\ODM\MongoDB\Mapping\Attribute\Indexes
{
}
