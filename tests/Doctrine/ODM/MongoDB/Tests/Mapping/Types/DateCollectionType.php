<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Types;

use Doctrine\ODM\MongoDB\Mapping\Types\Type;
use Doctrine\ODM\MongoDB\Mapping\Types\DateType;
use Doctrine\ODM\MongoDB\Tests\Mapping\Types\CustomTypeException;

/**
 * My custom datatype.
 */
class DateCollectionType extends Type
{
    public function convertToPHPValue($value)
    {
        throw new CustomTypeException('Currently converting to PHP value');

        // $converter = new DateType;
        // $value !== null ? array_values($value) : null;
        // if(is_array($value)){
        //     $value = array_map(function($date) use ($converter){
        //         return $converter->convertToPHPValue($date);
        //     }, $value);
        // }
        // return $value;
    }

    public function convertToDatabaseValue($value)
    {
        if(is_string($value)){
            throw new CustomTypeException('Currently converting to DB value');
        }
        $converter = new DateType;
        $value !== null ? array_values($value) : null;
        if(is_array($value)){
            $value = array_map(function ($date) use ($converter){
                return $converter->convertToDatabaseValue($date);
            }, $value);
        }
        return $value;
    }
}