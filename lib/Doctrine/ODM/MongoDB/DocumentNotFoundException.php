<?php

namespace Doctrine\ODM\MongoDB;

/**
 * Class for exception when encountering proxy object that has
 * an identifier that does not exist in the database.
 *
 * @since       1.0
 */
class DocumentNotFoundException extends MongoDBException
{
    public static function documentNotFound($className, $identifier)
    {
        return new self(sprintf(
            'The "%s" document with identifier %s could not be found.',
            $className, 
            json_encode($identifier)
        ));
    }
}
