<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Annotations;

use MongoDB\Driver\ClientEncryption;

enum EncryptQuery: string
{
    case Equality = ClientEncryption::QUERY_TYPE_EQUALITY;
    case Range    = ClientEncryption::QUERY_TYPE_RANGE;
}
