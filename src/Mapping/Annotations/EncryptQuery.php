<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Mapping\EncryptQuery;

use function class_exists;
use function trigger_deprecation;

trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Enum %s\\EncryptQuery is deprecated, use %s instead.', __NAMESPACE__, EncryptQuery::class);

class_exists(EncryptQuery::class);
