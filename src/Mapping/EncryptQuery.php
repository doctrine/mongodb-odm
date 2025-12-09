<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use MongoDB\Driver\ClientEncryption;

use function class_alias;

enum EncryptQuery: string
{
    case Equality = ClientEncryption::QUERY_TYPE_EQUALITY;
    case Range    = ClientEncryption::QUERY_TYPE_RANGE;
}

// @phpstan-ignore-next-line class.notFound
class_alias(EncryptQuery::class, Annotations\EncryptQuery::class);
