<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The BinData type for binary UUID data.
 *
 * Per the BSON specification, this sub-type is deprecated in favor of the
 * RFC 4122 UUID sub-type (BinDataUUIDRFC4122Type class).
 *
 * @since       1.0
 */
class BinDataUUIDType extends BinDataType
{
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_OLD_UUID;
}
