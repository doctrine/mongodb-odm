<?php

namespace Doctrine\ODM\MongoDB\Types;

class CustomType extends Type
{
    /**
     * @return string Redirects to the method convertToPHPValue from child class
     */
    final public function closureToPHP()
    {
        $fqcn = self::class;

        return sprintf('
            $type = \%s::getType($typeIdentifier);
            $return = $type->convertToPHPValue($value);', $fqcn);
    }
}
