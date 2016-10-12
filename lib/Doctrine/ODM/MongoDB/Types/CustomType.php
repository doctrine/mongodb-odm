<?php

namespace Doctrine\ODM\MongoDB\Types;

class CustomType extends Type
{
    /**
     * @return string Redirects to the method convertToPHPValue from child class
     */
    final public function closureToPHP()
    {
        $fqcn = get_class($this);

        return sprintf('
            $reflection = new \ReflectionClass("%s");
            $type = $reflection->newInstanceWithoutConstructor();
            $return = $type->convertToPHPValue($value);', $fqcn);
    }
}
