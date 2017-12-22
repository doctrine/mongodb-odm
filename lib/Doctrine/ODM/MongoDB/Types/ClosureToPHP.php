<?php

namespace Doctrine\ODM\MongoDB\Types;

trait ClosureToPHP
{
    /**
     * @return string Redirects to the method convertToPHPValue from child class
     */
    final public function closureToPHP()
    {
        return sprintf('
            $type = \%s::getType($typeIdentifier);
            $return = $type->convertToPHPValue($value);', Type::class);
    }
}
