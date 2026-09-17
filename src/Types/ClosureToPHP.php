<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/** This trait will be deprecated in 3.0 as this behavior will be used by default */
trait ClosureToPHP
{
    /** @return string Redirects to the method convertToPHPValue from child class */
    final public function closureToPHP(): string
    {
        return '$return = $this->class->getFieldType($fieldName)->convertToPHPValue($value);';
    }
}
