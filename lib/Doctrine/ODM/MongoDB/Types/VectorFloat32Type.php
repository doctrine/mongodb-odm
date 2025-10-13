<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\VectorType;

final class VectorFloat32Type extends AbstractVectorType
{
    protected function getVectorType(): VectorType
    {
        return VectorType::Float32;
    }
}
