<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Binary;

/**
 * The BinData type for binary MD5 data.
 *
 * Note: This sub-type is intended to store binary MD5 data. Considering using
 * the basic string field type for storing hexadecimal MD5 strings.
 */
class BinDataMD5Type extends BinDataType
{
    /** @var int */
    protected $binDataType = Binary::TYPE_MD5;
}
