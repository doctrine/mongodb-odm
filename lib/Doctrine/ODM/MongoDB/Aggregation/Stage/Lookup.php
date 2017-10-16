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
use Doctrine\MongoDB\Aggregation\Stage as BaseStage;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Lookup extends BaseStage\Lookup
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
     * @param Builder $builder
     * @param string $from
     * @param DocumentManager $documentManager
     * @param ClassMetadata $class
     */
    public function __construct(Builder $builder, $from, DocumentManager $documentManager, ClassMetadata $class)
    {
        $this->dm = $documentManager;
        $this->class = $class;

        parent::__construct($builder, $from);
    }

    /**
     * @param string $from
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
            return parent::from($from);
        }

        if ($this->targetClass->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInLookupStages($this->targetClass->name);
        }

        return parent::from($this->targetClass->getCollection());
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

        parent::from($this->targetClass->getCollection());

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
     * {@inheritdoc}
     */
    public function localField($localField)
    {
        return parent::localField($this->prepareFieldName($localField, $this->class));
    }

    /**
     * {@inheritdoc}
     */
    public function foreignField($foreignField)
    {
        return parent::foreignField($this->prepareFieldName($foreignField, $this->targetClass));
    }

    protected function prepareFieldName($fieldName, ClassMetadata $class = null)
    {
        if ( ! $class) {
            return $fieldName;
        }

        return $this->getDocumentPersister($class)->prepareFieldName($fieldName);
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister(ClassMetadata $class)
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
    }
}
