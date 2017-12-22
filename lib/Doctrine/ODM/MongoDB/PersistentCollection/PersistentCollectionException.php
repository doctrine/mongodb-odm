<?php

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * MongoDB ODM PersistentCollection Exception.
 *
 * @since 1.1
 */
class PersistentCollectionException extends MongoDBException
{
    public static function directoryNotWritable()
    {
        return new self('Your PersistentCollection directory must be writable.');
    }

    public static function directoryRequired()
    {
        return new self('You must configure a PersistentCollection directory. See docs for details.');
    }

    public static function namespaceRequired()
    {
        return new self('You must configure a PersistentCollection namespace. See docs for details');
    }

    /**
     * @param string          $className
     * @param string          $methodName
     * @param string          $parameterName
     * @param \Exception|null $previous
     *
     * @return self
     */
    public static function invalidParameterTypeHint(
        $className,
        $methodName,
        $parameterName,
        \Exception $previous = null
    ) {
        return new self(
            sprintf(
                'The type hint of parameter "%s" in method "%s" in class "%s" is invalid.',
                $parameterName,
                $methodName,
                $className
            ),
            0,
            $previous
        );
    }

    /**
     * @param $className
     * @param $methodName
     * @param \Exception|null $previous
     *
     * @return self
     */
    public static function invalidReturnTypeHint($className, $methodName, \Exception $previous = null)
    {
        return new self(
            sprintf(
                'The return type of method "%s" in class "%s" is invalid.',
                $methodName,
                $className
            ),
            0,
            $previous
        );
    }
}
