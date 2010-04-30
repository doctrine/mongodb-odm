<?php

namespace Doctrine\ODM\MongoDB;

class MongoDBException extends \Exception
{
    public static function mappingFileNotFound($className, $fileName)
    {
        return new self(sprintf(sprintf('Could not find mapping file "%s" for class "%s".', $fileName, $className)));
    }

    public static function documentNotMappedToDB($className)
    {
        return new self(sprintf('The "%s" document is not mapped to a MongoDB database.', $className));
    }

    public static function documentNotMappedToCollection($className)
    {
        return new self(sprintf('The "%s" document is not mapped to a MongoDB database collection.', $className));
    }
}