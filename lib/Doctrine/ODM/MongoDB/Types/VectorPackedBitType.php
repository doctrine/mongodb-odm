<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\VectorType;

final class VectorPackedBitType extends AbstractVectorType
{
    protected function getVectorType(): VectorType
    {
        return VectorType::PackedBit;
    }
}
