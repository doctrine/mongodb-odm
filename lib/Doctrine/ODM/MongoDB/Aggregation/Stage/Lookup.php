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

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\Common\Persistence\Mapping\MappingException as BaseMappingException;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Lookup extends Stage
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ClassMetadata
     */
    private $class;

    /**
     * @var ClassMetadata
     */
    private $targetClass;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $localField;

    /**
     * @var string
     */
    private $foreignField;

    /**
     * @var string
     */
    private $as;

    /**
     * @param Builder $builder
     * @param string $from
     * @param DocumentManager $documentManager
     * @param ClassMetadata $class
     */
    public function __construct(Builder $builder, $from, DocumentManager $documentManager, ClassMetadata $class)
    {
        parent::__construct($builder);

        $this->dm = $documentManager;
        $this->class = $class;

        $this->from($from);
    }

    /**
     * Specifies the name of the new array field to add to the input documents.
     *
     * The new array field contains the matching documents from the from
     * collection. If the specified name already exists in the input document,
     * the existing field is overwritten.
     *
     * @param string $alias
     *
     * @return $this
     */
    public function alias($alias)
    {
        $this->as = $alias;

        return $this;
    }

    /**
     * Specifies the collection or field name in the same database to perform the join with.
     *
     * The from collection cannot be sharded.
     *
     * @param string $from
     *
     * @return $this
     */
    public function from($from)
    {
        // $from can either be
        // a) a field name indicating a reference to a different document. Currently, only REFERENCE_STORE_AS_ID is supported
        // b) a Class name
        // c) a collection name
        // In cases b) and c) the local and foreign fields need to be filled
        if ($this->class->hasReference($from)) {
            return $this->fromReference($from);
        }

        // Check if mapped class with given name exists
        try {
            $this->targetClass = $this->dm->getClassMetadata($from);
        } catch (BaseMappingException $e) {
            $this->from = $from;
            return $this;
        }

        if ($this->targetClass->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInLookupStages($this->targetClass->name);
        }

        $this->from = $this->targetClass->getCollection();
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$lookup' => [
                'from' => $this->from,
                'localField' => $this->localField,
                'foreignField' => $this->foreignField,
                'as' => $this->as,
            ]
        ];
    }

    /**
     * Specifies the field from the documents input to the $lookup stage.
     *
     * $lookup performs an equality match on the localField to the foreignField
     * from the documents of the from collection. If an input document does not
     * contain the localField, the $lookup treats the field as having a value of
     * null for matching purposes.
     *
     * @param string $localField
     *
     * @return $this
     */
    public function localField($localField)
    {
        $this->localField = $this->prepareFieldName($localField, $this->class);
        return $this;
    }

    /**
     * Specifies the field from the documents in the from collection.
     *
     * $lookup performs an equality match on the foreignField to the localField
     * from the input documents. If a document in the from collection does not
     * contain the foreignField, the $lookup treats the value as null for
     * matching purposes.
     *
     * @param string $foreignField
     *
     * @return $this
     */
    public function foreignField($foreignField)
    {
        $this->foreignField = $this->prepareFieldName($foreignField, $this->targetClass);
        return $this;
    }

    protected function prepareFieldName($fieldName, ClassMetadata $class = null)
    {
        if ( ! $class) {
            return $fieldName;
        }

        return $this->getDocumentPersister($class)->prepareFieldName($fieldName);
    }

    /**
     * @param string $fieldName
     * @return $this
     * @throws MappingException
     */
    private function fromReference($fieldName)
    {
        if (! $this->class->hasReference($fieldName)) {
            MappingException::referenceMappingNotFound($this->class->name, $fieldName);
        }

        $referenceMapping = $this->class->getFieldMapping($fieldName);
        $this->targetClass = $this->dm->getClassMetadata($referenceMapping['targetDocument']);
        if ($this->targetClass->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInLookupStages($this->targetClass->name);
        }

        $this->from = $this->targetClass->getCollection();

        if ($referenceMapping['isOwningSide']) {
            switch ($referenceMapping['storeAs']) {
                case ClassMetadataInfo::REFERENCE_STORE_AS_ID:
                case ClassMetadataInfo::REFERENCE_STORE_AS_REF:
                    $referencedFieldName = ClassMetadataInfo::getReferenceFieldName($referenceMapping['storeAs'], $referenceMapping['name']);
                    break;

                default:
                    throw MappingException::cannotLookupDbRefReference($this->class->name, $fieldName);
            }

            $this
                ->foreignField('_id')
                ->localField($referencedFieldName);
        } else {
            if (isset($referenceMapping['repositoryMethod']) || ! isset($referenceMapping['mappedBy'])) {
                throw MappingException::repositoryMethodLookupNotAllowed($this->class->name, $fieldName);
            }

            $mappedByMapping = $this->targetClass->getFieldMapping($referenceMapping['mappedBy']);
            switch ($mappedByMapping['storeAs']) {
                case ClassMetadataInfo::REFERENCE_STORE_AS_ID:
                case ClassMetadataInfo::REFERENCE_STORE_AS_REF:
                    $referencedFieldName = ClassMetadataInfo::getReferenceFieldName($mappedByMapping['storeAs'], $mappedByMapping['name']);
                    break;

                default:
                    throw MappingException::cannotLookupDbRefReference($this->class->name, $fieldName);
            }

            $this
                ->localField('_id')
                ->foreignField($referencedFieldName);
        }

        return $this;
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister(ClassMetadata $class)
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
    }
}
