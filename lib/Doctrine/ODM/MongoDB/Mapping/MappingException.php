<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Mapping\MappingException as BaseMappingException;
use function sprintf;

/**
 * Class for all exceptions related to the Doctrine MongoDB ODM
 *
 */
class MappingException extends BaseMappingException
{
    /**
     * @param string $name
     * @return MappingException
     */
    public static function typeExists($name)
    {
        return new self(sprintf('Type %s already exists.', $name));
    }

    /**
     * @param string $name
     * @return MappingException
     */
    public static function typeNotFound($name)
    {
        return new self(sprintf('Type to be overwritten %s does not exist.', $name));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function mappingNotFound($className, $fieldName)
    {
        return new self(sprintf("No mapping found for field '%s' in class '%s'.", $fieldName, $className));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function referenceMappingNotFound($className, $fieldName)
    {
        return new self(sprintf("No reference mapping found for field '%s' in class '%s'.", $fieldName, $className));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function mappingNotFoundInClassNorDescendants($className, $fieldName)
    {
        return new self(sprintf("No mapping found for field '%s' in class '%s' nor its descendants.", $fieldName, $className));
    }

    /**
     * @param string $fieldName
     * @param string $className
     * @param string $className2
     * @return MappingException
     */
    public static function referenceFieldConflict($fieldName, $className, $className2)
    {
        return new self(sprintf("Reference mapping for field '%s' in class '%s' conflicts with one mapped in class '%s'.", $fieldName, $className, $className2));
    }

    /**
     * @param string $className
     * @param string $dbFieldName
     * @return MappingException
     */
    public static function mappingNotFoundByDbName($className, $dbFieldName)
    {
        return new self(sprintf("No mapping found for field by DB name '%s' in class '%s'.", $dbFieldName, $className));
    }

    /**
     * @param string $document
     * @param string $fieldName
     * @return MappingException
     */
    public static function duplicateFieldMapping($document, $fieldName)
    {
        return new self(sprintf('Property "%s" in "%s" was already declared, but it must be declared only once', $fieldName, $document));
    }

    /**
     * @param string $document
     * @param string $fieldName
     * @return MappingException
     */
    public static function discriminatorFieldConflict($document, $fieldName)
    {
        return new self(sprintf('Discriminator field "%s" in "%s" conflicts with a mapped field\'s "name" attribute.', $fieldName, $document));
    }

    /**
     * Throws an exception that indicates that a class used in a discriminator map does not exist.
     * An example would be an outdated (maybe renamed) classname.
     *
     * @param string $className   The class that could not be found
     * @param string $owningClass The class that declares the discriminator map.
     * @return MappingException
     */
    public static function invalidClassInDiscriminatorMap($className, $owningClass)
    {
        return new self(sprintf("Document class '%s' used in the discriminator map of class '%s' does not exist.", $className, $owningClass));
    }

    /**
     * Throws an exception that indicates a discriminator value does not exist in a map
     *
     * @param string $value       The discriminator value that could not be found
     * @param string $owningClass The class that declares the discriminator map
     * @return MappingException
     */
    public static function invalidDiscriminatorValue($value, $owningClass)
    {
        return new self(sprintf("Discriminator value '%s' used in the declaration of class '%s' does not exist.", $value, $owningClass));
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function missingFieldName($className)
    {
        return new self(sprintf("The Document class '%s' field mapping misses the 'fieldName' attribute.", $className));
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function classIsNotAValidDocument($className)
    {
        return new self(sprintf('Class %s is not a valid document or mapped super class.', $className));
    }

    /**
     * Exception for reflection exceptions - adds the document name,
     * because there might be long classnames that will be shortened
     * within the stacktrace
     *
     * @param string $document The document's name
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
        return new self(sprintf("No identifier/primary key specified for Document '%s'. Every Document must have an identifier/primary key.", $documentName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function missingIdentifierField($className, $fieldName)
    {
        return new self(sprintf('The identifier %s is missing for a query of %s', $fieldName, $className));
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function missingIdGeneratorClass($className)
    {
        return new self(sprintf('The class-option for the custom ID generator is missing in class %s.', $className));
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function classIsNotAValidGenerator($className)
    {
        return new self(sprintf('The class %s if not a valid ID generator of type AbstractIdGenerator.', $className));
    }

    /**
     * @param string $className
     * @param string $optionName
     * @return MappingException
     */
    public static function missingGeneratorSetter($className, $optionName)
    {
        return new self(sprintf('The class %s is missing a setter for the option %s.', $className, $optionName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function cascadeOnEmbeddedNotAllowed($className, $fieldName)
    {
        return new self(sprintf('Cascade on %s::%s is not allowed.', $className, $fieldName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function simpleReferenceRequiresTargetDocument($className, $fieldName)
    {
        return new self(sprintf('Target document must be specified for simple reference: %s::%s', $className, $fieldName));
    }

    /**
     * @param string $targetDocument
     * @return MappingException
     */
    public static function simpleReferenceMustNotTargetDiscriminatedDocument($targetDocument)
    {
        return new self(sprintf('Simple reference must not target document using Single Collection Inheritance, %s targeted.', $targetDocument));
    }

    /**
     * @param string $strategy
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function atomicCollectionStrategyNotAllowed($strategy, $className, $fieldName)
    {
        return new self(sprintf('%s collection strategy can be used only in top level document, used in %s::%s', $strategy, $className, $fieldName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function owningAndInverseReferencesRequireTargetDocument($className, $fieldName)
    {
        return new self(sprintf('Target document must be specified for owning/inverse sides of reference: %s::%s', $className, $fieldName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function mustNotChangeIdentifierFieldsType($className, $fieldName)
    {
        return new self(sprintf('%s::%s was declared an identifier and must stay this way.', $className, $fieldName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $strategy
     * @return MappingException
     */
    public static function referenceManySortMustNotBeUsedWithNonSetCollectionStrategy($className, $fieldName, $strategy)
    {
        return new self(sprintf("ReferenceMany's sort can not be used with addToSet and pushAll strategies, %s used in %s::%s", $strategy, $className, $fieldName));
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
        return new self(sprintf('Invalid strategy %s used in %s::%s with type %s', $strategy, $className, $fieldName, $type));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $collectionClass
     * @return MappingException
     */
    public static function collectionClassDoesNotImplementCommonInterface($className, $fieldName, $collectionClass)
    {
        return new self(sprintf('%s used as custom collection class for %s::%s has to implement %s interface.', $collectionClass, $className, $fieldName, Collection::class));
    }

    /**
     * @param string $subclassName
     * @return MappingException
     */
    public static function shardKeyInSingleCollInheritanceSubclass($subclassName)
    {
        return new self(sprintf('Shard key overriding in subclass is forbidden for single collection inheritance: %s', $subclassName));
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function embeddedDocumentCantHaveShardKey($className)
    {
        return new self(sprintf("Embedded document can't have shard key: %s", $className));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function onlySetStrategyAllowedInShardKey($className, $fieldName)
    {
        return new self(sprintf('Only fields using the SET strategy can be used in the shard key: %s::%s', $className, $fieldName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function noMultiKeyShardKeys($className, $fieldName)
    {
        return new self(sprintf('No multikey indexes are allowed in the shard key: %s::%s', $className, $fieldName));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function cannotLookupDbRefReference($className, $fieldName)
    {
        return new self(sprintf("Cannot use reference '%s' in class '%s' for lookup or graphLookup: dbRef references are not supported.", $fieldName, $className));
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return MappingException
     */
    public static function repositoryMethodLookupNotAllowed($className, $fieldName)
    {
        return new self(sprintf("Cannot use reference '%s' in class '%s' for lookup or graphLookup. repositoryMethod is not supported in \$lookup and \$graphLookup stages.", $fieldName, $className));
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function cannotUseShardedCollectionInOutStage($className)
    {
        return new self(sprintf("Cannot use class '%s' as collection for out stage. Sharded collections are not allowed.", $className));
    }

    /**
     * @param string $className
     * @return MappingException
     */
    public static function cannotUseShardedCollectionInLookupStages($className)
    {
        return new self(sprintf("Cannot use class '%s' as collection for lookup or graphLookup stage. Sharded collections are not allowed.", $className));
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return MappingException
     */
    public static function referencePrimersOnlySupportedForInverseReferenceMany($className, $fieldName)
    {
        return new self(sprintf("Cannot use reference priming on '%s' in class '%s'. Reference priming is only supported for inverse references", $fieldName, $className));
    }

    public static function connectFromFieldMustReferenceSameDocument($fieldName)
    {
        return new self(sprintf("Cannot use field '%s' as connectFromField in a \$graphLookup stage. Reference must target the document itself.", $fieldName));
    }

    public static function repositoryMethodCanNotBeCombinedWithSkipLimitAndSort($className, $fieldName)
    {
        return new self(sprintf("'repositoryMethod' used on '%s' in class '%s' can not be combined with skip, limit or sort.", $fieldName, $className));
    }
}
