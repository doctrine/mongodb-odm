<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The BinData type for function data.
 *
 * @since       1.0
 */
class BinDataFuncType extends BinDataType
{
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_FUNCTION;
}
