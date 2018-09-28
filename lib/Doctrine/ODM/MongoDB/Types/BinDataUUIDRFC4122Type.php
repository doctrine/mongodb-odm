<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Binary;

/**
 * The BinData type for binary UUID data, which follows RFC 4122.
 */
class BinDataUUIDRFC4122Type extends BinDataType
{
    /** @var int */
    protected $binDataType = Binary::TYPE_UUID;
}
