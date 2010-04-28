<?php

namespace Doctrine\ODM\MongoDB;

class MongoDBException extends \Exception
{
    public static function entityNotMappedToDB($className)
    {
        return new self(sprintf('The "%s" entity is not mapped to a MongoDB database.', $className));
    }

    public static function entityNotMappedToCollection($className)
    {
        return new self(sprintf('The "%s" entity is not mapped to a MongoDB database collection.', $className));
    }
}