<?php
namespace Doctrine\ODM\MongoDB\Types;

class SerializedType extends Type
{
    /**
     * {@inheritDoc}
     */
    public function convertToDatabaseValue($value)
    {
        return new \MongoBinData(serialize($value), \MongoBinData::BYTE_ARRAY);
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value)
    {
        $binaryData = $value->bin;
        if ($binaryData === null) {
            return null;
        }

        $binaryData = (is_resource($binaryData)) ? stream_get_contents($binaryData) : $binaryData;
        $val = unserialize($binaryData);
        if ($val === false && $binaryData !== 'b:0;') {
            throw new \LogicException('Conversion exception: ' . $binaryData);
        }

        return $val;
    }

    /**
     * {@inheritDoc}
     */
    public function closureToMongo()
    {
        return '$return = \MongoBinData(serialize($value), \MongoBinData::BYTE_ARRAY);';
    }

    /**
     * {@inheritDoc}
     */
    public function closureToPHP()
    {
        return '$return = unserialize($value->bin);';
    }
}
