<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The BinData type for custom binary data.
 *
 * @since       1.0
 */
class BinDataCustomType extends BinDataType
{
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_USER_DEFINED;
}
