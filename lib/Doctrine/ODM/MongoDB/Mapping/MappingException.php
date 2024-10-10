<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations\AbstractDocument;
use Doctrine\Persistence\Mapping\MappingException as BaseMappingException;
use ReflectionException;
use ReflectionObject;

use function sprintf;

/**
 * Class for all exceptions related to the Doctrine MongoDB ODM
 */
final class MappingException extends BaseMappingException
{
    public static function typeExists(string $name): self
    {
        return new self(sprintf('Type %s already exists.', $name));
    }

    public static function typeNotFound(string $name): self
    {
        return new self(sprintf('Type to be overwritten %s does not exist.', $name));
    }

    public static function typeRequirementsNotFulfilled(string $className, string $fieldName, string $type, string $reason): self
    {
        return new self(sprintf("Can not use '%s' type for field '%s' in class '%s' as its requirements are not met: %s.", $fieldName, $className, $type, $reason));
    }

    public static function mappingNotFound(string $className, string $fieldName): self
    {
        return new self(sprintf("No mapping found for field '%s' in class '%s'.", $fieldName, $className));
    }

    public static function referenceMappingNotFound(string $className, string $fieldName): self
    {
        return new self(sprintf("No reference mapping found for field '%s' in class '%s'.", $fieldName, $className));
    }

    public static function mappingNotFoundInClassNorDescendants(string $className, string $fieldName): self
    {
        return new self(sprintf("No mapping found for field '%s' in class '%s' nor its descendants.", $fieldName, $className));
    }

    public static function referenceFieldConflict(string $fieldName, string $className, string $className2): self
    {
        return new self(sprintf("Reference mapping for field '%s' in class '%s' conflicts with one mapped in class '%s'.", $fieldName, $className, $className2));
    }

    public static function mappingNotFoundByDbName(string $className, string $dbFieldName): self
    {
        return new self(sprintf("No mapping found for field by DB name '%s' in class '%s'.", $dbFieldName, $className));
    }

    public static function duplicateDatabaseFieldName(string $document, string $offendingFieldName, string $databaseName, string $originalFieldName): self
    {
        return new self(sprintf('Field "%s" in class "%s" is mapped to field "%s" in the database, but that name is already in use by field "%s".', $offendingFieldName, $document, $databaseName, $originalFieldName));
    }

    public static function discriminatorFieldConflict(string $document, string $fieldName): self
    {
        return new self(sprintf('Discriminator field "%s" in "%s" conflicts with a mapped field\'s "name" attribute.', $fieldName, $document));
    }

    public static function invalidClassInDiscriminatorMap(string $className, string $owningClass): self
    {
        return new self(sprintf("Document class '%s' used in the discriminator map of class '%s' does not exist.", $className, $owningClass));
    }

    public static function invalidClassInReferenceDiscriminatorMap(string $className, string $owningClass, string $fieldName): self
    {
        return new self(sprintf("Document class '%s' used in the discriminator map of field '%s' in class '%s' does not exist.", $className, $fieldName, $owningClass));
    }

    public static function unlistedClassInDiscriminatorMap(string $className): self
    {
        return new self(sprintf('Document class "%s" is unlisted in the discriminator map.', $className));
    }

    public static function invalidDiscriminatorValue(string $value, string $owningClass): self
    {
        return new self(sprintf("Discriminator value '%s' used in the declaration of class '%s' does not exist.", $value, $owningClass));
    }

    public static function invalidTargetDocument(string $targetDocument, string $owningClass, string $owningField): self
    {
        return new self(sprintf("Target document class '%s' used in field '%s' of class '%s' does not exist.", $targetDocument, $owningField, $owningClass));
    }

    public static function missingFieldName(string $className): self
    {
        return new self(sprintf("The Document class '%s' field mapping misses the 'fieldName' attribute.", $className));
    }

    public static function classIsNotAValidDocument(string $className): self
    {
        return new self(sprintf('Class %s is not a valid document or mapped super class.', $className));
    }

    public static function classCanOnlyBeMappedByOneAbstractDocument(string $className, AbstractDocument $mappedAs, AbstractDocument $offending): self
    {
        return new self(sprintf(
            "Can not map class '%s' as %s because it was already mapped as %s.",
            $className,
            (new ReflectionObject($offending))->getShortName(),
            (new ReflectionObject($mappedAs))->getShortName(),
        ));
    }

    public static function reflectionFailure(string $document, ReflectionException $previousException): self
    {
        return new self('An error occurred in ' . $document, 0, $previousException);
    }

    public static function identifierRequired(string $documentName): self
    {
        return new self(sprintf("No identifier/primary key specified for Document '%s'. Every Document must have an identifier/primary key.", $documentName));
    }

    public static function missingIdGeneratorClass(string $className): self
    {
        return new self(sprintf('The class-option for the custom ID generator is missing in class %s.', $className));
    }

    public static function classIsNotAValidGenerator(string $className): self
    {
        return new self(sprintf('The class %s if not a valid ID generator of type AbstractIdGenerator.', $className));
    }

    public static function missingGeneratorSetter(string $className, string $optionName): self
    {
        return new self(sprintf('The class %s is missing a setter for the option %s.', $className, $optionName));
    }

    public static function cascadeOnEmbeddedNotAllowed(string $className, string $fieldName): self
    {
        return new self(sprintf('Cascade on %s::%s is not allowed.', $className, $fieldName));
    }

    public static function simpleReferenceRequiresTargetDocument(string $className, string $fieldName): self
    {
        return new self(sprintf('Target document must be specified for identifier reference: %s::%s', $className, $fieldName));
    }

    public static function simpleReferenceMustNotTargetDiscriminatedDocument(string $targetDocument): self
    {
        return new self(sprintf('Identifier reference must not target document using Single Collection Inheritance, %s targeted.', $targetDocument));
    }

    public static function atomicCollectionStrategyNotAllowed(string $strategy, string $className, string $fieldName): self
    {
        return new self(sprintf('%s collection strategy can be used only in top level document, used in %s::%s', $strategy, $className, $fieldName));
    }

    public static function owningAndInverseReferencesRequireTargetDocument(string $className, string $fieldName): self
    {
        return new self(sprintf('Target document must be specified for owning/inverse sides of reference: %s::%s', $className, $fieldName));
    }

    public static function mustNotChangeIdentifierFieldsType(string $className, string $fieldName): self
    {
        return new self(sprintf('%s::%s was declared an identifier and must stay this way.', $className, $fieldName));
    }

    public static function referenceManySortMustNotBeUsedWithNonSetCollectionStrategy(string $className, string $fieldName, string $strategy): self
    {
        return new self(sprintf("ReferenceMany's sort can not be used with addToSet and pushAll strategies, %s used in %s::%s", $strategy, $className, $fieldName));
    }

    public static function invalidStorageStrategy(string $className, string $fieldName, string $type, string $strategy): self
    {
        return new self(sprintf('Invalid strategy %s used in %s::%s with type %s', $strategy, $className, $fieldName, $type));
    }

    public static function collectionClassDoesNotImplementCommonInterface(string $className, string $fieldName, string $collectionClass): self
    {
        return new self(sprintf('%s used as custom collection class for %s::%s has to implement %s interface.', $collectionClass, $className, $fieldName, Collection::class));
    }

    public static function shardKeyInSingleCollInheritanceSubclass(string $subclassName): self
    {
        return new self(sprintf('Shard key overriding in subclass is forbidden for single collection inheritance: %s', $subclassName));
    }

    public static function embeddedDocumentCantHaveShardKey(string $className): self
    {
        return new self(sprintf("Embedded document can't have shard key: %s", $className));
    }

    public static function onlySetStrategyAllowedInShardKey(string $className, string $fieldName): self
    {
        return new self(sprintf('Only fields using the SET strategy can be used in the shard key: %s::%s', $className, $fieldName));
    }

    public static function noMultiKeyShardKeys(string $className, string $fieldName): self
    {
        return new self(sprintf('No multikey indexes are allowed in the shard key: %s::%s', $className, $fieldName));
    }

    public static function cannotLookupDbRefReference(string $className, string $fieldName): self
    {
        return new self(sprintf("Cannot use reference '%s' in class '%s' for lookup or graphLookup: dbRef references are not supported.", $fieldName, $className));
    }

    public static function repositoryMethodLookupNotAllowed(string $className, string $fieldName): self
    {
        return new self(sprintf("Cannot use reference '%s' in class '%s' for lookup or graphLookup. repositoryMethod is not supported in \$lookup and \$graphLookup stages.", $fieldName, $className));
    }

    public static function cannotUseShardedCollectionInOutStage(string $className): self
    {
        return new self(sprintf("Cannot use class '%s' as collection for out stage. Sharded collections are not allowed.", $className));
    }

    public static function referencePrimersOnlySupportedForInverseReferenceMany(string $className, string $fieldName): self
    {
        return new self(sprintf("Cannot use reference priming on '%s' in class '%s'. Reference priming is only supported for inverse references", $fieldName, $className));
    }

    public static function connectFromFieldMustReferenceSameDocument(string $fieldName): self
    {
        return new self(sprintf("Cannot use field '%s' as connectFromField in a \$graphLookup stage. Reference must target the document itself.", $fieldName));
    }

    public static function repositoryMethodCanNotBeCombinedWithSkipLimitAndSort(string $className, string $fieldName): self
    {
        return new self(sprintf("'repositoryMethod' used on '%s' in class '%s' can not be combined with skip, limit or sort.", $fieldName, $className));
    }

    public static function xmlMappingFileInvalid(string $filename, string $errorDetails): self
    {
        return new self(sprintf("The mapping file %s is invalid: \n%s", $filename, $errorDetails));
    }

    public static function fieldNotAllowedForGridFS(string $className, string $fieldName): self
    {
        return new self(sprintf("Field '%s' in class '%s' is not a valid field for GridFS documents. You should move it to an embedded metadata document.", $fieldName, $className));
    }

    public static function discriminatorNotAllowedForGridFS(string $className): self
    {
        return new self(sprintf("Class '%s' cannot be discriminated because it is marked as a GridFS file", $className));
    }

    public static function invalidRepositoryClass(string $className, string $repositoryClass, string $expectedRepositoryClass): self
    {
        return new self(sprintf(
            'Invalid repository class "%s" for mapped class "%s". It must be an instance of "%s".',
            $repositoryClass,
            $className,
            $expectedRepositoryClass,
        ));
    }

    public static function viewWithoutRootClass(string $className): self
    {
        return new self(sprintf('Class "%s" mapped as view without must have a root class.', $className));
    }

    public static function viewRootClassNotFound(string $className, string $rootClass): self
    {
        return new self(sprintf('Root class "%s" for view "%s" could not be found.', $rootClass, $className));
    }

    public static function schemaValidationError(int $errorCode, string $errorMessage, string $className, string $property): self
    {
        return new self(sprintf('The following schema validation error occurred while parsing the "%s" property of the "%s" class: "%s" (code %s).', $property, $className, $errorMessage, $errorCode));
    }

    public static function nonEnumTypeMapped(string $className, string $fieldName, string $enumType): self
    {
        return new self(sprintf(
            'Attempting to map a non-enum type %s as an enum: %s::%s',
            $enumType,
            $className,
            $fieldName,
        ));
    }

    public static function nonBackedEnumMapped(string $className, string $fieldName, string $enumType): self
    {
        return new self(sprintf(
            'Attempting to map a non-backed enum %s: %s::%s',
            $enumType,
            $className,
            $fieldName,
        ));
    }

    public static function emptySearchIndexDefinition(string $className, string $indexName): self
    {
        return new self(sprintf('%s search index "%s" must be dynamic or specify a field mapping', $className, $indexName));
    }

    public static function timeSeriesFieldNotFound(string $className, string $fieldName, string $field): self
    {
        return new self(sprintf(
            'The %s field %s::%s was not found',
            $field,
            $className,
            $fieldName,
        ));
    }
}
