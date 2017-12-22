<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The BinData type for binary UUID data, which follows RFC 4122.
 *
 * @since       1.0
 */
class BinDataUUIDRFC4122Type extends BinDataType
{
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_UUID;
}
