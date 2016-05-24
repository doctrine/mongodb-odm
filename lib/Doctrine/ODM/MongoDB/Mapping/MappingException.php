<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\Persistence\Mapping\MappingException as BaseMappingException;

/**
 * Class for all exceptions related to the Doctrine MongoDB ODM
 *
 * @since       1.0
 */
class MappingException extends BaseMappingException
{
    /**
     * @param string $name
     * @return MappingException
     */
    public static function typeExists($name)
    {
        return new self('Type ' . $name . ' already exists.');
    }

    /**
     * @param string $name
     * @return MappingException
     */
    public static function typeNotFound($name)
    {
        return new self('Type to be overwritten ' . $name . ' does not exist.');
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function mappingNotFound($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' in class '$className'.");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function mappingNotFoundInClassNorDescendants($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' in class '$className' nor its descendants.");
    }

    /**
     * @param $fieldName
     * @param $className
     * @param $className2
     * @return MappingException
     */
    public static function referenceFieldConflict($fieldName, $className, $className2)
    {
        return new self("Reference mapping for field '$fieldName' in class '$className' conflicts with one mapped in class '$className2'.");
    }

    /**
     * @param string $className
     * @param string $dbFieldName
     * @return MappingException
     */
    public static function mappingNotFoundByDbName($className, $dbFieldName)
    {
        return new self("No mapping found for field by DB name '$dbFieldName' in class '$className'.");
    }

    /**
     * @param string $document
     * @param string $fieldName
     * @return MappingException
     */
    public static function duplicateFieldMapping($document, $fieldName)
    {
        return new self('Property "' . $fieldName . '" in "' . $document . '" was already declared, but it must be declared only once');
    }

    /**
     * @param string $document
     * @param string $fieldName
     * @return MappingException
     */
    public static function discriminatorFieldConflict($document, $fieldName)
    {
        return new self('Discriminator field "' . $fieldName . '" in "' . $document . '" conflicts with a mapped field\'s "name" attribute.');
    }

    /**
     * Throws an exception that indicates that a class used in a discriminator map does not exist.
     * An example would be an outdated (maybe renamed) classname.
     *
     * @param string $className The class that could not be found
     * @param string $owningClass The class that declares the discriminator map.
     * @return MappingException
     */
    public static function invalidClassInDiscriminatorMap($className, $owningClass)
    {
        return new self(
            "Document class '$className' used in the discriminator map of class '$owningClass' " .
            'does not exist.'
        );
    }

    /**
     * Throws an exception that indicates a discriminator value does not exist in a map
     *
     * @param string $value The discriminator value that could not be found
     * @param string $owningClass The class that declares the discriminator map
     * @return MappingException
     */
    public static function invalidDiscriminatorValue($value, $owningClass)
    {
        return new self("Discriminator value '$value' used in the declaration of class '$owningClass' does not exist.");
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function missingFieldName($className)
    {
        return new self("The Document class '$className' field mapping misses the 'fieldName' attribute.");
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function classIsNotAValidDocument($className)
    {
        return new self('Class ' . $className . ' is not a valid document or mapped super class.');
    }

    /**
     * Exception for reflection exceptions - adds the document name,
     * because there might be long classnames that will be shortened
     * within the stacktrace
     *
     * @param string $document The document's name
     * @param \ReflectionException $previousException
     * @return \Doctrine\ODM\MongoDB\Mapping\MappingException
     */
    public static function reflectionFailure($document, \ReflectionException $previousException)
    {
        return new self('An error occurred in ' . $document, 0, $previousException);
    }

    /**
     * @param string $documentName
     * @return MappingException
     */
    public static function identifierRequired($documentName)
    {
        return new self("No identifier/primary key specified for Document '$documentName'."
            . ' Every Document must have an identifier/primary key.');
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function missingIdentifierField($className, $fieldName)
    {
        return new self("The identifier $fieldName is missing for a query of " . $className);
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function missingIdGeneratorClass($className)
    {
        return new self("The class-option for the custom ID generator is missing in class $className.");
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function classIsNotAValidGenerator($className)
    {
        return new self("The class $className if not a valid ID generator of type AbstractIdGenerator.");
    }

    /**
     * @param string $className
     * @param string $optionName
     * @return MappingException
     */
    public static function missingGeneratorSetter($className, $optionName)
    {
        return new self("The class $className is missing a setter for the option $optionName.");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function cascadeOnEmbeddedNotAllowed($className, $fieldName)
    {
        return new self("Cascade on $className::$fieldName is not allowed.");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function simpleReferenceRequiresTargetDocument($className, $fieldName)
    {
        return new self("Target document must be specified for simple reference: $className::$fieldName");
    }

    /**
     * @param string $targetDocument
     * @return MappingException
     */
    public static function simpleReferenceMustNotTargetDiscriminatedDocument($targetDocument)
    {
        return new self("Simple reference must not target document using Single Collection Inheritance, $targetDocument targeted.");
    }

    /**
     * @param string $strategy
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function atomicCollectionStrategyNotAllowed($strategy, $className, $fieldName)
    {
        return new self("$strategy collection strategy can be used only in top level document, used in $className::$fieldName");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function owningAndInverseReferencesRequireTargetDocument($className, $fieldName)
    {
        return new self("Target document must be specified for owning/inverse sides of reference: $className::$fieldName");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function mustNotChangeIdentifierFieldsType($className, $fieldName)
    {
        return new self("$className::$fieldName was declared an identifier and must stay this way.");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $strategy
     * @return MappingException
     */
    public static function referenceManySortMustNotBeUsedWithNonSetCollectionStrategy($className, $fieldName, $strategy)
    {
        return new self("ReferenceMany's sort can not be used with addToSet and pushAll strategies, $strategy used in $className::$fieldName");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $type
     * @param string $strategy
     * @return MappingException
     */
    public static function invalidStorageStrategy($className, $fieldName, $type, $strategy)
    {
        return new self("Invalid strategy $strategy used in $className::$fieldName with type $type");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $collectionClass
     * @return MappingException
     */
    public static function collectionClassDoesNotImplementCommonInterface($className, $fieldName, $collectionClass)
    {
        return new self("$collectionClass used as custom collection class for $className::$fieldName has to implement Doctrine\\Common\\Collections\\Collection interface.");
    }

    /**
     * @param $subclassName
     * @return MappingException
     */
    public static function shardKeyInSingleCollInheritanceSubclass($subclassName)
    {
        return new self("Shard key overriding in subclass is forbidden for single collection inheritance: $subclassName");
    }

    /**
     * @param $className
     * @return MappingException
     */
    public static function embeddedDocumentCantHaveShardKey($className)
    {
        return new self("Embedded document can't have shard key: $className");
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function onlySetStrategyAllowedInShardKey($className, $fieldName)
    {
        return new self("Only fields using the SET strategy can be used in the shard key: $className::$fieldName");
    }

    /**
     * @param $className
     * @param $fieldName
     * @return MappingException
     */
    public static function noMultiKeyShardKeys($className, $fieldName)
    {
        return new self("No multikey indexes are allowed in the shard key: $className::$fieldName");
    }
}
