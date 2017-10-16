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
use Doctrine\ODM\MongoDB\Types\Type;

class GraphLookup extends BaseStage\GraphLookup
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
     * @param string $from Target collection for the $graphLookup operation to
     * search, recursively matching the connectFromField to the connectToField.
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

    public function connectFromField($connectFromField)
    {
        // No targetClass mapping - simply use field name as is
        if ( ! $this->targetClass) {
            return parent::connectFromField($connectFromField);
        }

        // connectFromField doesn't have to be a reference - in this case, just convert the field name
        if ( ! $this->targetClass->hasReference($connectFromField)) {
            return parent::connectFromField($this->convertTargetFieldName($connectFromField));
        }

        // connectFromField is a reference - do a sanity check
        $referenceMapping = $this->targetClass->getFieldMapping($connectFromField);
        if ($referenceMapping['targetDocument'] !== $this->targetClass->name) {
            throw MappingException::connectFromFieldMustReferenceSameDocument($connectFromField);
        }

        return parent::connectFromField($this->getReferencedFieldName($connectFromField, $referenceMapping));
    }

    public function connectToField($connectToField)
    {
        return parent::connectToField($this->convertTargetFieldName($connectToField));
    }

    /**
     * @param string $fieldName
     * @return $this
     * @throws MappingException
     */
    private function fromReference($fieldName)
    {
        if ( ! $this->class->hasReference($fieldName)) {
            MappingException::referenceMappingNotFound($this->class->name, $fieldName);
        }

        $referenceMapping = $this->class->getFieldMapping($fieldName);
        $this->targetClass = $this->dm->getClassMetadata($referenceMapping['targetDocument']);
        if ($this->targetClass->isSharded()) {
            throw MappingException::cannotUseShardedCollectionInLookupStages($this->targetClass->name);
        }

        parent::from($this->targetClass->getCollection());

        $referencedFieldName = $this->getReferencedFieldName($fieldName, $referenceMapping);

        if ($referenceMapping['isOwningSide']) {
            $this
                ->startWith('$' . $referencedFieldName)
                ->connectToField('_id');
        } else {
            $this
                ->startWith('$' . $referencedFieldName)
                ->connectToField('_id');
        }

        // A self-reference indicates that we can also fill the "connectFromField" accordingly
        if ($this->targetClass->name === $this->class->name) {
            $this->connectFromField($referencedFieldName);
        }

        return $this;
    }

    protected function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map([$this, 'convertExpression'], $expression);
        } elseif (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister($this->class)->prepareFieldName(substr($expression, 1));
        } else {
            return Type::convertPHPToDatabaseValue(parent::convertExpression($expression));
        }
    }

    protected function convertTargetFieldName($fieldName)
    {
        if (is_array($fieldName)) {
            return array_map([$this, 'convertTargetFieldName'], $fieldName);
        }

        if ( ! $this->targetClass) {
            return $fieldName;
        }

        return $this->getDocumentPersister($this->targetClass)->prepareFieldName($fieldName);
    }

    /**
     * @param ClassMetadata $class
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister(ClassMetadata $class)
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
    }

    private function getReferencedFieldName($fieldName, array $mapping)
    {
        if ( ! $mapping['isOwningSide']) {
            if (isset($mapping['repositoryMethod']) || ! isset($mapping['mappedBy'])) {
                throw MappingException::repositoryMethodLookupNotAllowed($this->class->name, $fieldName);
            }

            $mapping = $this->targetClass->getFieldMapping($mapping['mappedBy']);
        }

        switch ($mapping['storeAs']) {
            case ClassMetadataInfo::REFERENCE_STORE_AS_ID:
            case ClassMetadataInfo::REFERENCE_STORE_AS_REF:
                return ClassMetadataInfo::getReferenceFieldName($mapping['storeAs'], $mapping['name']);
                break;

            default:
                throw MappingException::cannotLookupDbRefReference($this->class->name, $fieldName);
        }
    }
}
