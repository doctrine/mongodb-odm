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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\UnitOfWork;

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
 * @since  1.0
 */
class ReferencePrimer
{
    /**
     * The default primer Closure.
     *
     * @var \Closure
     */
    private $defaultPrimer;

    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager $dm
     */
    private $dm;

    /**
     * The UnitOfWork instance.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * Initializes this instance with the specified document manager and unit of work.
     *
     * @param DocumentManager $dm Document manager.
     * @param UnitOfWork $uow Unit of work.
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow)
    {
        $this->dm = $dm;
        $this->uow = $uow;

        $this->defaultPrimer = function(DocumentManager $dm, ClassMetadata $class, array $ids, array $hints) {
            $qb = $dm->createQueryBuilder($class->name)
                ->field($class->identifier)->in($ids);

            if ( ! empty($hints[Query::HINT_SLAVE_OKAY])) {
                $qb->slaveOkay(true);
            }

            if ( ! empty($hints[Query::HINT_READ_PREFERENCE])) {
                $qb->setReadPreference($hints[Query::HINT_READ_PREFERENCE], $hints[Query::HINT_READ_PREFERENCE_TAGS]);
            }

            $qb->getQuery()->execute()->toArray(false);
        };
    }


    /**
     * Prime references within a mapped field of one or more documents.
     *
     * If a $primer callable is provided, it should have the same signature as
     * the default primer defined in the constructor. If $primer is not
     * callable, the default primer will be used.
     *
     * @param ClassMetadata      $class     Class metadata for the document
     * @param array|\Traversable $documents Documents containing references to prime
     * @param string             $fieldName Field name containing references to prime
     * @param array              $hints     UnitOfWork hints for priming queries
     * @param callable           $primer    Optional primer callable
     * @throws \InvalidArgumentException If the mapped field is not the owning
     *                                   side of a reference relationship.
     * @throws \InvalidArgumentException If $primer is not callable
     * @throws \LogicException If the mapped field is a simple reference and is
     *                         missing a target document class.
     */
    public function primeReferences(ClassMetadata $class, $documents, $fieldName, array $hints = array(), $primer = null)
    {
        $data = $this->parseDotSyntaxForPrimer($fieldName, $class, $documents);
        $mapping = $data['mapping'];
        $fieldName = $data['fieldName'];
        $class = $data['class'];
        $documents = $data['documents'];

        /* Inverse-side references would need to be populated before we can
         * collect references to be primed. This is not supported.
         */
        if ( ! isset($mapping['reference']) || ! $mapping['isOwningSide']) {
            throw new \InvalidArgumentException(sprintf('Field "%s" is not the owning side of a reference relationship in class "%s"', $fieldName, $class->name));
        }

        /* Simple reference require a target document class so we can construct
         * the priming query.
         */
        if ($mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID && empty($mapping['targetDocument'])) {
            throw new \LogicException(sprintf('Field "%s" is a simple reference without a target document class in class "%s"', $fieldName, $class->name));
        }

        if ($primer !== null && ! is_callable($primer)) {
            throw new \InvalidArgumentException('$primer is not callable');
        }

        $primer = $primer ?: $this->defaultPrimer;
        $groupedIds = array();

        /* @var $document PersistentCollectionInterface */
        foreach ($documents as $document) {
            $fieldValue = $class->getFieldValue($document, $fieldName);

            /* The field will need to be either a Proxy (reference-one) or
             * PersistentCollection (reference-many) in order to prime anything.
             */
            if ( ! is_object($fieldValue)) {
                continue;
            }

            if ($mapping['type'] === 'one' && $fieldValue instanceof Proxy && ! $fieldValue->__isInitialized()) {
                $refClass = $this->dm->getClassMetadata(get_class($fieldValue));
                $id = $this->uow->getDocumentIdentifier($fieldValue);
                $groupedIds[$refClass->name][serialize($id)] = $id;
            } elseif ($mapping['type'] == 'many' && $fieldValue instanceof PersistentCollectionInterface) {
                $this->addManyReferences($fieldValue, $groupedIds);
            }
        }

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
     * @param string             $fieldName
     * @param ClassMetadata      $class
     * @param array|\Traversable $documents
     * @param array              $mapping
     * @return array
     */
    private function parseDotSyntaxForPrimer($fieldName, $class, $documents, $mapping = null)
    {
        // Recursion passthrough:
        if ($mapping != null) {
            return array('fieldName' => $fieldName, 'class' => $class, 'documents' => $documents, 'mapping' => $mapping);
        }

        // Gather mapping data:
        $e = explode('.', $fieldName);

        if ( ! isset($class->fieldMappings[$e[0]])) {
            throw new \InvalidArgumentException(sprintf('Field %s cannot be further parsed for priming because it is unmapped.', $fieldName));
        }

        $mapping = $class->fieldMappings[$e[0]];
        $e[0] = $mapping['fieldName'];

        // Case of embedded document(s) to recurse through:
        if ( ! isset($mapping['reference'])) {
            if (empty($mapping['embedded'])) {
                throw new \InvalidArgumentException(sprintf('Field "%s" of fieldName "%s" is not an embedded document, therefore no children can be primed. Aborting. This feature does not support traversing nested referenced documents at this time.', $e[0], $fieldName));
            }

            if ( ! isset($mapping['targetDocument'])) {
                throw new \InvalidArgumentException(sprintf('No target document class has been specified for this embedded document. However, targetDocument mapping must be specified in order for prime to work on fieldName "%s" for mapping of field "%s".', $fieldName, $mapping['fieldName']));
            }

            $childDocuments = array();

            foreach ($documents as $document) {
                $fieldValue = $class->getFieldValue($document, $e[0]);

                if ($fieldValue instanceof PersistentCollectionInterface) {
                    foreach ($fieldValue as $elemDocument) {
                        array_push($childDocuments, $elemDocument);
                    }
                } else {
                    array_push($childDocuments,$fieldValue);
                }
            }

            array_shift($e);

            $childClass = $this->dm->getClassMetadata($mapping['targetDocument']);

            if ( ! $childClass->hasField($e[0])) {
                throw new \InvalidArgumentException(sprintf('Field to prime must exist in embedded target document. Reference fieldName "%s" for mapping of target document class "%s".', $fieldName, $mapping['targetDocument']));
            }

            $childFieldName = implode('.',$e);

            return $this->parseDotSyntaxForPrimer($childFieldName, $childClass, $childDocuments);
        }

        // Case of reference(s) to prime:
        if ($mapping['reference']) {
            if (count($e) > 1) {
                throw new \InvalidArgumentException(sprintf('Cannot prime more than one layer deep but field "%s" is a reference and has children in fieldName "%s".', $e[0], $fieldName));
            }

            return array('fieldName' => $fieldName, 'class' => $class, 'documents' => $documents, 'mapping' => $mapping);
        }
    }

    /**
     * Adds identifiers from a PersistentCollection to $groupedIds.
     *
     * If the relation contains simple references, the mapping is assumed to
     * have a target document class defined. Without that, there is no way to
     * infer the class of the referenced documents.
     *
     * @param PersistentCollectionInterface $persistentCollection
     * @param array                $groupedIds
     */
    private function addManyReferences(PersistentCollectionInterface $persistentCollection, array &$groupedIds)
    {
        $mapping = $persistentCollection->getMapping();

        if ($mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
            $className = $mapping['targetDocument'];
            $class = $this->dm->getClassMetadata($className);
        }

        foreach ($persistentCollection->getMongoData() as $reference) {
            if ($mapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
                $id = $reference;
            } else {
                $id = $reference['$id'];
                $className = $this->uow->getClassNameForAssociation($mapping, $reference);
                $class = $this->dm->getClassMetadata($className);
            }

            $document = $this->uow->tryGetById($id, $class);

            if ( ! $document || ($document instanceof Proxy && ! $document->__isInitialized())) {
                $id = $class->getPHPIdentifierValue($id);
                $groupedIds[$className][serialize($id)] = $id;
            }
        }
    }
}
