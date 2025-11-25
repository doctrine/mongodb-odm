<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Mapping\Attribute\ChangeTrackingPolicy;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Namespace "Doctrine\ODM\MongoDB\Mapping\Annotations" is deprecated, use "Doctrine\ODM\MongoDB\Mapping\Attribute" instead.');

class_exists(ChangeTrackingPolicy::class);
