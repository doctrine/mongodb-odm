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
            $targetMapping = $this->dm->getClassMetadata($from);
            return parent::from($targetMapping->getCollection());
        } catch (BaseMappingException $e) {
            return parent::from($from);
        }
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
        $targetMapping = $this->dm->getClassMetadata($referenceMapping['targetDocument']);
        parent::from($targetMapping->getCollection());

        if ($referenceMapping['isOwningSide']) {
            if ($referenceMapping['storeAs'] !== ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
                throw MappingException::cannotLookupNonIdReference($this->class->name, $fieldName);
            }

            $this
                ->foreignField('_id')
                ->localField($referenceMapping['name']);
        } else {
            if (isset($referenceMapping['repositoryMethod'])) {
                throw MappingException::repositoryMethodLookupNotAllowed($this->class->name, $fieldName);
            }

            $mappedByMapping = $targetMapping->getFieldMapping($referenceMapping['mappedBy']);
            if ($mappedByMapping['storeAs'] !== ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
                throw MappingException::cannotLookupNonIdReference($this->class->name, $fieldName);
            }

            $this
                ->localField('_id')
                ->foreignField($mappedByMapping['name']);
        }

        return $this;
    }
}
