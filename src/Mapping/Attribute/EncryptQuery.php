<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Attribute;

use MongoDB\Driver\ClientEncryption;

use function class_alias;

enum EncryptQuery: string
{
    case Equality = ClientEncryption::QUERY_TYPE_EQUALITY;
    case Range    = ClientEncryption::QUERY_TYPE_RANGE;
}

// @phpstan-ignore class.notFound
class_alias(EncryptQuery::class, \Doctrine\ODM\MongoDB\Mapping\Annotations\EncryptQuery::class);
