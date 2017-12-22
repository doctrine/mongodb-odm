<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The BinData type for binary MD5 data.
 *
 * Note: This sub-type is intended to store binary MD5 data. Considering using
 * the basic string field type for storing hexadecimal MD5 strings.
 *
 * @since       1.0
 */
class BinDataMD5Type extends BinDataType
{
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_MD5;
}
