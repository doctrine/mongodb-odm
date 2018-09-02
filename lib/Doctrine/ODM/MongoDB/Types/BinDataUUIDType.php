<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Binary;

/**
 * The BinData type for binary UUID data.
 *
 * Per the BSON specification, this sub-type is deprecated in favor of the
 * RFC 4122 UUID sub-type (BinDataUUIDRFC4122Type class).
 */
class BinDataUUIDType extends BinDataType
{
    /** @var int */
    protected $binDataType = Binary::TYPE_OLD_UUID;
}
