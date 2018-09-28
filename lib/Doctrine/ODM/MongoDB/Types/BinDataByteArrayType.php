<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Binary;

/**
 * The BinData type for byte array data.
 *
 * Per the BSON specification, this sub-type is deprecated in favor of the
 * generic sub-type (BinDataType class).
 */
class BinDataByteArrayType extends BinDataType
{
    /** @var int */
    protected $binDataType = Binary::TYPE_OLD_BINARY;
}
