<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Binary;

/**
 * The BinData type for function data.
 */
class BinDataFuncType extends BinDataType
{
    /** @var int */
    protected $binDataType = Binary::TYPE_FUNCTION;
}
