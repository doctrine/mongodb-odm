<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(\Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractIndex::class);

return;

/** @deprecated Use \Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractIndex instead */
abstract class AbstractIndex extends \Doctrine\ODM\MongoDB\Mapping\Attribute\AbstractIndex
{
}
