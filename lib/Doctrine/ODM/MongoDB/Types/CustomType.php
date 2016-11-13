<?php

namespace Doctrine\ODM\MongoDB\Types;

trait CustomType
{
    /**
     * @return string Redirects to the method convertToPHPValue from child class
     */
    final public function closureToPHP()
    {
        $fqcn = Type::class;

        return sprintf('
            $type = \%s::getType($typeIdentifier);
            $return = $type->convertToPHPValue($value);', $fqcn);
    }
}
