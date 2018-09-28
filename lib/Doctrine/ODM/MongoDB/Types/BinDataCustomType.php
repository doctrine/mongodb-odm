<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Binary;

/**
 * The BinData type for custom binary data.
 */
class BinDataCustomType extends BinDataType
{
    /** @var int */
    protected $binDataType = Binary::TYPE_USER_DEFINED;
}
