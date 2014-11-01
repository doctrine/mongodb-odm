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
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;

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
 * @author Jeremy Mikola <jmikola@gmail.com>
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
     * Constructor.
     *
     * @param DocumentManager $dm
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

            $qb->getQuery()->toArray();
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
        $data = $this->parseDotSyntaxForPrimer($fieldName,$class,$documents);
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
        if ( ! empty($mapping['simple']) && empty($mapping['targetDocument'])) {
            throw new \LogicException(sprintf('Field "%s" is a simple reference without a target document class in class "%s"', $fieldName, $class->name));
        }

        if ($primer !== null && ! is_callable($primer)) {
            throw new \InvalidArgumentException('$primer is not callable');
        }

        $primer = $primer ?: $this->defaultPrimer;
        $groupedIds = array();

        /** @var Doctrine\ODM\MongoDB\PersistentCollection $document */
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
            } elseif ($mapping['type'] == 'many' && $fieldValue instanceof PersistentCollection) {
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
     * This addresses Issue #624.
     *
     * @param array $fieldName
     * @param ClassMetadata $class
     * @param array\Traversable $documents
     * @param array $mapping
     */
    private function parseDotSyntaxForPrimer($fieldName, $class, $documents, $mapping = null) {

        // Recursion passthrough:
        if($mapping != null) return array('fieldName'=>$fieldName, 'class'=>$class, 'documents'=>$documents, 'mapping'=>$mapping);

            echo("Mapping null, parsing dot syntax for fieldName $fieldName.\n");

        //Create an array of all the field names in the dot-syntax list.
        $e = explode('.', $fieldName);

        // No further processing for unmapped fields
        if ( ! isset($class->fieldMappings[$e[0]]))
            throw new \InvalidArgumentException("Field $fieldName cannot be further parsed for priming because it is unmpaped..");

        // Get the field mappings for the first item.
        $mapping = $class->fieldMappings[$e[0]];
        $e[0] = $mapping['name']; // This seems redundant

            echo("Found mapping for $e[0]: \n");
            //print_r($mapping);

        if(!isset($mapping['reference'])) goto checkIfEmbedded;
        if($mapping['reference']) {

                echo("Mapping is a reference. Making sure it's not deep. \n");

            // If it's a reference but not the last item, abort because we only go one reference in at this time.
            if(count($e) > 1){
                throw new \InvalidArgumentException("Cannot prime more than one layer deep but field $e[0] is a reference and has children in fieldName $fieldName.");
            } else goto complete; // Jump to end for code legibility.
        }

        checkIfEmbedded:
        if(!isset($mapping['embedded'])) {
            throw new \InvalidArgumentException("Field $e[0] of fieldName $fieldName is not an embedded document, therefore no children can be primed. Aborting.");
        } elseif(!$mapping['embedded']) {
            throw new \InvalidArgumentException("Field $e[0] of fieldName $fieldName is not an embedded document, therefore no children can be primed. Aborting.");
        }

        // From here down we know the mapping is embedded and we must go deeper in.

        // Exception for fields without a targetDocument mapping
        if ( ! isset($mapping['targetDocument']))
            throw new \InvalidArgumentException("targetDocument mapping must be set in order for prime to work. Reference fieldName $fieldName for mapping of field ".$mapping['fieldName']);

        echo("Checking documents to form nextDocuments array. \n");
        $nextDocuments = array();

        foreach($documents as $document) {
            echo("Getting fieldValue for fieldName $e[0].\n");
            $fieldValue = $class->getFieldValue($document, $e[0]);
            if(is_a($fieldValue,'Doctrine\ODM\MongoDB\PersistentCollection')) {
                foreach($fieldValue as $subDocument) {
                    array_push($nextDocuments,$subDocument);
                }
            }
            else array_push($nextDocuments,$fieldValue);
        }

        // Set our next class to be that of the target embedded document.
        $nextClass = $this->dm->getClassMetadata($mapping['targetDocument']);
        // Remove the current field off the list.
        array_shift($e);

        // Throw an exception if the next class does not have metadata for the next field.
        if ( ! $nextClass->hasField($e[0]))
            throw new \InvalidArgumentException("Field to prime must exist in embedded target document. Reference fieldName $fieldName for mapping of target document class ".$mapping['targetDocument']);

        // Prepare the remaining part of the dot-syntax.
        $childFieldName = implode('.',$e);

        // Go deeper into recursion.
        return $this->parseDotSyntaxForPrimer($childFieldName, $nextClass, $nextDocuments);

        complete:

            //echo("Completing parsing of dot-syntax. Returning fieldName: $fieldName, class: ".$class->getName().", mapping: ".json_encode($mapping)."\n");

        $return = array('fieldName'=>$fieldName, 'class'=>$class, 'documents'=>$documents, 'mapping'=>$mapping);

            //echo("Checking return fieldname: ".$return['fieldName'].", class: ".$return['class']->getName().", mapping: ".json_encode($return['mapping'])."\n");

        // If it's the last item and it's the reference, prime it.
        return $return;

    }

    /**
     * Adds identifiers from a PersistentCollection to $groupedIds.
     *
     * If the relation contains simple references, the mapping is assumed to
     * have a target document class defined. Without that, there is no way to
     * infer the class of the referenced documents.
     *
     * @param PersistentCollection $persistentCollection
     * @param array                $groupedIds
     */
    private function addManyReferences(PersistentCollection $persistentCollection, array &$groupedIds)
    {
        $mapping = $persistentCollection->getMapping();

        if ( ! empty($mapping['simple'])) {
            $className = $mapping['targetDocument'];
            $class = $this->dm->getClassMetadata($className);
        }

        foreach ($persistentCollection->getMongoData() as $reference) {
            if ( ! empty($mapping['simple'])) {
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
