<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The BinData type for generic data.
 *
 * @since       1.0
 */
class BinDataType extends Type
{
    /**
     * Data type for binary data
     *
     * @var integer
     * @see http://bsonspec.org/#/specification
     */
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_GENERIC;

    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if ( ! $value instanceof \MongoDB\BSON\Binary) {
            return new \MongoDB\BSON\Binary($value, $this->binDataType);
        }

        if ($value->getType() !== $this->binDataType) {
            return new \MongoDB\BSON\Binary($value->getData(), $this->binDataType);
        }

        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? ($value instanceof \MongoDB\BSON\Binary ? $value->getData() : $value) : null;
    }

    public function closureToMongo()
    {
        return sprintf('$return = $value !== null ? new \MongoDB\BSON\Binary($value, %d) : null;', $this->binDataType);
    }

    public function closureToPHP()
    {
        return '$return = $value !== null ? ($value instanceof \MongoDB\BSON\Binary ? $value->getData() : $value) : null;';
    }
}
