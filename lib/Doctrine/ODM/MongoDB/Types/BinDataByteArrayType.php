<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The BinData type for byte array data.
 *
 * Per the BSON specification, this sub-type is deprecated in favor of the
 * generic sub-type (BinDataType class).
 *
 * @since       1.0
 */
class BinDataByteArrayType extends BinDataType
{
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_OLD_BINARY;
}
