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
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * Query expression builder for ODM.
 *
 * @since       1.0
 */
class Expr extends \Doctrine\MongoDB\Query\Expr
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The ClassMetadata instance for the document being queried
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Sets ClassMetadata for document being queried.
     *
     * @param ClassMetadata $class
     */
    public function setClassMetadata(ClassMetadata $class)
    {
        $this->class = $class;
    }

    /**
     * Checks that the value of the current field is a reference to the supplied document.
     *
     * @param object $document
     * @return Expr
     */
    public function references($document)
    {
        if ($this->currentField) {
            $mapping = $this->getReferenceMapping();
            $dbRef = $this->dm->createDBRef($document, $mapping);
            $storeAs = array_key_exists('storeAs', $mapping) ? $mapping['storeAs'] : null;

            if ($storeAs === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
                $this->query[$mapping['name']] = $dbRef;
            } else {
                $keys = array('ref' => true, 'id' => true, 'db' => true);

                if ($storeAs === ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF) {
                    unset($keys['db']);
                }

                if (isset($mapping['targetDocument'])) {
                    unset($keys['ref'], $keys['db']);
                }

                foreach ($keys as $key => $value) {
                    $this->query[$mapping['name'] . '.$' . $key] = $dbRef['$' . $key];
                }
            }
        } else {
            $dbRef = $this->dm->createDBRef($document);
            $this->query = $dbRef;
        }

        return $this;
    }

    /**
     * Checks that the current field includes a reference to the supplied document.
     *
     * @param object $document
     * @return Expr
     */
    public function includesReferenceTo($document)
    {
        if ($this->currentField) {
            $mapping = $this->getReferenceMapping();
            $dbRef = $this->dm->createDBRef($document, $mapping);
            $storeAs = array_key_exists('storeAs', $mapping) ? $mapping['storeAs'] : null;

            if ($storeAs === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
                $this->query[$mapping['name']] = $dbRef;
            } else {
                $keys = array('ref' => true, 'id' => true, 'db' => true);

                if ($storeAs === ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF) {
                    unset($keys['db']);
                }

                if (isset($mapping['targetDocument'])) {
                    unset($keys['ref'], $keys['db']);
                }

                foreach ($keys as $key => $value) {
                    $this->query[$mapping['name']]['$elemMatch']['$' . $key] = $dbRef['$' . $key];
                }
            }
        } else {
            $dbRef = $this->dm->createDBRef($document);
            $this->query['$elemMatch'] = $dbRef;
        }

        return $this;
    }

    /**
     * Gets prepared query part of expression.
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->dm->getUnitOfWork()
            ->getDocumentPersister($this->class->name)
            ->prepareQueryOrNewObj($this->query);
    }

    /**
     * Gets prepared newObj part of expression.
     *
     * @return array
     */
    public function getNewObj()
    {
        return $this->dm->getUnitOfWork()
            ->getDocumentPersister($this->class->name)
            ->prepareQueryOrNewObj($this->newObj, true);
    }

    /**
     * Gets reference mapping for current field from current class or its descendants.
     *
     * @return array
     * @throws MappingException
     */
    private function getReferenceMapping()
    {
        $mapping = null;
        try {
            $mapping = $this->class->getFieldMapping($this->currentField);
        } catch (MappingException $e) {
            if (empty($this->class->discriminatorMap)) {
                throw $e;
            }
            $foundIn = null;
            foreach ($this->class->discriminatorMap as $child) {
                $childClass = $this->dm->getClassMetadata($child);
                if ($childClass->hasAssociation($this->currentField)) {
                    if ($mapping !== null && $mapping !== $childClass->getFieldMapping($this->currentField)) {
                        throw MappingException::referenceFieldConflict($this->currentField, $foundIn->name, $childClass->name);
                    }
                    $mapping = $childClass->getFieldMapping($this->currentField);
                    $foundIn = $childClass;
                }
            }
            if ($mapping === null) {
                throw MappingException::mappingNotFoundInClassNorDescendants($this->class->name, $this->currentField);
            }
        }
        return $mapping;
    }
}
