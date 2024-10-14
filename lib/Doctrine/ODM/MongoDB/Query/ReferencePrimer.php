<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;
use InvalidArgumentException;
use LogicException;
use Traversable;

use function array_push;
use function array_shift;
use function array_values;
use function assert;
use function call_user_func;
use function count;
use function explode;
use function implode;
use function is_object;
use function serialize;
use function sprintf;

/**
 * The ReferencePrimer is responsible for priming reference relationships.
 *
 * Priming a field mapped as either reference-one or reference-many will load
 * the referenced document(s) eagerly and avoid individual lazy loading through
 * proxy object initialization.
 *
 * Priming can only be used for the owning side side of a relationship, since
 * the referenced identifiers are not immediately available on an inverse side.
 *
 * @internal
 *
 * @phpstan-import-type FieldMapping from ClassMetadata
 * @phpstan-import-type Hints from UnitOfWork
 */
final class ReferencePrimer
{
    /**
     * The DocumentManager instance.
     */
    private DocumentManager $dm;

    /**
     * The UnitOfWork instance.
     */
    private UnitOfWork $uow;

    public function __construct(DocumentManager $dm, UnitOfWork $uow)
    {
        $this->dm  = $dm;
        $this->uow = $uow;
    }

    /**
     * Prime references within a mapped field of one or more documents.
     *
     * If a $primer callable is provided, it should have the same signature as
     * the default primer defined in the constructor. If $primer is not
     * callable, the default primer will be used.
     *
     * @param ClassMetadata<object>             $class     Class metadata for the document
     * @param array<object>|Traversable<object> $documents Documents containing references to prime
     * @param string                            $fieldName Field name containing references to prime
     * @param array                             $hints     UnitOfWork hints for priming queries
     * @param callable|null                     $primer    Optional primer callable
     * @phpstan-param Hints $hints
     *
     * @throws InvalidArgumentException If the mapped field is not the owning
     *                                   side of a reference relationship.
     * @throws LogicException If the mapped field is a simple reference and is
     *                         missing a target document class.
     */
    public function primeReferences(ClassMetadata $class, $documents, string $fieldName, array $hints = [], ?callable $primer = null): void
    {
        $data      = $this->parseDotSyntaxForPrimer($fieldName, $class, $documents);
        $mapping   = $data['mapping'];
        $fieldName = $data['fieldName'];
        $class     = $data['class'];
        $documents = $data['documents'];

        /* Inverse-side references would need to be populated before we can
         * collect references to be primed. This is not supported.
         */
        if (! isset($mapping['reference']) || ! $mapping['isOwningSide']) {
            throw new InvalidArgumentException(sprintf('Field "%s" is not the owning side of a reference relationship in class "%s"', $fieldName, $class->name));
        }

        /* Simple reference require a target document class so we can construct
         * the priming query.
         */
        if ($mapping['storeAs'] === ClassMetadata::REFERENCE_STORE_AS_ID && empty($mapping['targetDocument'])) {
            throw new LogicException(sprintf('Field "%s" is an identifier reference without a target document class in class "%s"', $fieldName, $class->name));
        }

        $primer     = $primer ?: self::defaultPrimer(...);
        $groupedIds = [];

        foreach ($documents as $document) {
            $fieldValue = $class->getFieldValue($document, $fieldName);

            /* The field will need to be either a Proxy (reference-one) or
             * PersistentCollection (reference-many) in order to prime anything.
             */
            if (! is_object($fieldValue)) {
                continue;
            }

            if ($mapping['type'] === ClassMetadata::ONE && $this->uow->isUninitializedObject($fieldValue)) {
                $refClass                                    = $this->dm->getClassMetadata($fieldValue::class);
                $id                                          = $this->uow->getDocumentIdentifier($fieldValue);
                $groupedIds[$refClass->name][serialize($id)] = $id;
            } elseif ($mapping['type'] === ClassMetadata::MANY && $fieldValue instanceof PersistentCollectionInterface) {
                $this->addManyReferences($fieldValue, $groupedIds);
            }
        }

        /** @var class-string $className */
        foreach ($groupedIds as $className => $ids) {
            $refClass = $this->dm->getClassMetadata($className);
            call_user_func($primer, $this->dm, $refClass, array_values($ids), $hints);
        }
    }

    /**
     * If you are priming references inside an embedded document you'll need to parse the dot syntax.
     * This method will traverse through embedded documents to find the reference to prime.
     * However this method will not traverse through multiple layers of references.
     * I.e. you can prime this: myDocument.embeddedDocument.embeddedDocuments.embeddedDocuments.referencedDocument(s)
     * ... but you cannot prime this: myDocument.embeddedDocument.referencedDocuments.referencedDocument(s)
     * This addresses Issue #624.
     *
     * @param ClassMetadata<object>             $class
     * @param array<object>|Traversable<object> $documents
     * @param FieldMapping|null                 $mapping
     *
     * @return array{fieldName: string, class: ClassMetadata<object>, documents: array<object>|Traversable<object>, mapping: FieldMapping}
     */
    private function parseDotSyntaxForPrimer(string $fieldName, ClassMetadata $class, $documents, ?array $mapping = null): array
    {
        // Recursion passthrough:
        if ($mapping !== null) {
            return ['fieldName' => $fieldName, 'class' => $class, 'documents' => $documents, 'mapping' => $mapping];
        }

        // Gather mapping data:
        $e = explode('.', $fieldName);

        if (! isset($class->fieldMappings[$e[0]])) {
            throw new InvalidArgumentException(sprintf('Field %s cannot be further parsed for priming because it is unmapped.', $fieldName));
        }

        $mapping = $class->fieldMappings[$e[0]];
        $e[0]    = $mapping['fieldName'];

        // Case of embedded document(s) to recurse through:
        if (! isset($mapping['reference'])) {
            if (empty($mapping['embedded'])) {
                throw new InvalidArgumentException(sprintf('Field "%s" of fieldName "%s" is not an embedded document, therefore no children can be primed. Aborting. This feature does not support traversing nested referenced documents at this time.', $e[0], $fieldName));
            }

            if (! isset($mapping['targetDocument'])) {
                throw new InvalidArgumentException(sprintf('No target document class has been specified for this embedded document. However, targetDocument mapping must be specified in order for prime to work on fieldName "%s" for mapping of field "%s".', $fieldName, $mapping['fieldName']));
            }

            $childDocuments = [];

            foreach ($documents as $document) {
                $fieldValue = $class->getFieldValue($document, $e[0]);

                if ($fieldValue instanceof PersistentCollectionInterface) {
                    foreach ($fieldValue as $elemDocument) {
                        array_push($childDocuments, $elemDocument);
                    }
                } else {
                    array_push($childDocuments, $fieldValue);
                }
            }

            array_shift($e);

            $childClass = $this->dm->getClassMetadata($mapping['targetDocument']);

            if (! $childClass->hasField($e[0])) {
                throw new InvalidArgumentException(sprintf('Field to prime must exist in embedded target document. Reference fieldName "%s" for mapping of target document class "%s".', $fieldName, $mapping['targetDocument']));
            }

            $childFieldName = implode('.', $e);

            return $this->parseDotSyntaxForPrimer($childFieldName, $childClass, $childDocuments);
        }

        // Case of reference(s) to prime:
        if ($mapping['reference']) {
            if (count($e) > 1) {
                throw new InvalidArgumentException(sprintf('Cannot prime more than one layer deep but field "%s" is a reference and has children in fieldName "%s".', $e[0], $fieldName));
            }

            return ['fieldName' => $fieldName, 'class' => $class, 'documents' => $documents, 'mapping' => $mapping];
        }

        throw new LogicException('Unable to parse property path for ReferencePrimer. Please report an issue in Doctrine\'s MongoDB ODM.');
    }

    /**
     * Adds identifiers from a PersistentCollection to $groupedIds.
     *
     * If the relation contains simple references, the mapping is assumed to
     * have a target document class defined. Without that, there is no way to
     * infer the class of the referenced documents.
     *
     * @param PersistentCollectionInterface<array-key, object> $persistentCollection
     * @param array<class-string, array<string, mixed>>        $groupedIds
     */
    private function addManyReferences(PersistentCollectionInterface $persistentCollection, array &$groupedIds): void
    {
        $mapping   = $persistentCollection->getMapping();
        $class     = null;
        $className = null;

        if ($mapping['storeAs'] === ClassMetadata::REFERENCE_STORE_AS_ID) {
            $className = $mapping['targetDocument'];
            $class     = $this->dm->getClassMetadata($className);
        }

        foreach ($persistentCollection->getMongoData() as $reference) {
            $id = ClassMetadata::getReferenceId($reference, $mapping['storeAs']);

            if ($mapping['storeAs'] !== ClassMetadata::REFERENCE_STORE_AS_ID) {
                $className = $this->dm->getClassNameForAssociation($mapping, $reference);
                $class     = $this->dm->getClassMetadata($className);
            }

            if ($class === null) {
                continue;
            }

            $document = $this->uow->tryGetById($id, $class);

            if ($document && ! $this->uow->isUninitializedObject($document)) {
                continue;
            }

            $id                                     = $class->getPHPIdentifierValue($id);
            $groupedIds[$className][serialize($id)] = $id;
        }
    }

    /**
     * @param list<mixed>       $ids
     * @param array<int, mixed> $hints
     */
    private static function defaultPrimer(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints): void
    {
        if ($class->identifier === null) {
            return;
        }

        $qb = $dm->createQueryBuilder($class->name)
            ->field($class->identifier)->in($ids);

        if (! empty($hints[Query::HINT_READ_PREFERENCE])) {
            $qb->setReadPreference($hints[Query::HINT_READ_PREFERENCE]);
        }

        $iterator = $qb->getQuery()->execute();
        assert($iterator instanceof Iterator);
        $iterator->toArray();
    }
}
