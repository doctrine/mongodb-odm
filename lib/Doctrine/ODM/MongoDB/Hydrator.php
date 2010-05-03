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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;

/**
 * The Hydrator class is responsible for converting a document from MongoDB
 * which is an array to classes and collections based on the mapping of the document
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Hydrator
{
    /**
     * The DocumentManager associationed with this Hydrator
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $_dm;

    /**
     * Create a new Hydrator instance
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->_dm = $dm;
    }

    /**
     * Hydrate array of MongoDB document data into the given document object
     * based on the mapping information provided in the ClassMetadata instance.
     *
     * @param ClassMetadata $metadata  The ClassMetadata instance for mapping information.
     * @param string $document  The document object to hydrate the data into.
     * @param array $data The array of document data.
     * @return array $values The array of hydrated values.
     */
    public function hydrate(ClassMetadata $metadata, $document, $data)
    {
        $values = array();
        foreach ($metadata->fieldMappings as $mapping) {
            if (isset($data[$mapping['fieldName']]) && isset($mapping['embedded'])) {
                $embeddedMetadata = $this->_dm->getClassMetadata($mapping['targetDocument']);
                $embeddedDocument = $embeddedMetadata->newInstance();
                if ($mapping['type'] === 'many') {
                    $documents = new ArrayCollection();
                    foreach ($data[$mapping['fieldName']] as $docArray) {
                        $doc = clone $embeddedDocument;
                        $this->hydrate($embeddedMetadata, $doc, $docArray);
                        $documents->add($doc);
                    }
                    $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
                    $value = $documents;
                } else {
                    $value = clone $embeddedDocument;
                    $this->hydrate($embeddedMetadata, $value, $data[$mapping['fieldName']]);
                    $metadata->setFieldValue($document, $mapping['fieldName'], $value);
                }
            } else if (isset($data[$mapping['fieldName']])) {
                $value = $data[$mapping['fieldName']];
                $metadata->setFieldValue($document, $mapping['fieldName'], $value);
            }
            if (isset($mapping['reference'])) {
                $targetMetadata = $this->_dm->getClassMetadata($mapping['targetDocument']);
                $targetDocument = $targetMetadata->newInstance();
                $value = isset($data[$mapping['fieldName']]) ? $data[$mapping['fieldName']] : null;
                if ($mapping['type'] === 'one' && isset($value['$id'])) {
                    $id = (string) $value['$id'];
                    $proxy = $this->_dm->getReference($mapping['targetDocument'], $id);
                    $metadata->setFieldValue($document, $mapping['fieldName'], $proxy);
                } else if ($mapping['type'] === 'many' && (is_array($value) || $value instanceof Collection)) {
                    $documents = new PersistentCollection($this->_dm, $targetMetadata, new ArrayCollection());
                    $documents->setInitialized(false);
                    foreach ($value as $v) {
                        $id = (string) $v['$id'];
                        $proxy = $this->_dm->getReference($mapping['targetDocument'], $id);
                        $documents->add($proxy);
                    }
                    $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
                }
            }
            if (isset($value)) {
                $values[$mapping['fieldName']] = $value;
            }
        }
        if (isset($data['_id'])) {
            $metadata->setIdentifierValue($document, (string) $data['_id']);
        }
        return $values;
    }
}