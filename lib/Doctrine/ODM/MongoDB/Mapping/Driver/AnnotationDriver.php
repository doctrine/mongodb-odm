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

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\Common\Annotations\AnnotationReader;

require __DIR__ . '/DoctrineAnnotations.php';

/**
 * The AnnotationDriver reads the mapping metadata from docblock annotations.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class AnnotationDriver implements Driver
{
    /**
     * The AnnotationReader.
     *
     * @var AnnotationReader
     */
    private $_reader;

    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    private $_paths = array();

    /**
     * Initializes a new AnnotationDriver that uses the given AnnotationReader for reading
     * docblock annotations.
     * 
     * @param $reader The AnnotationReader to use.
     * @param string|array $paths One or multiple paths where mapping classes can be found. 
     */
    public function __construct(AnnotationReader $reader, $paths = null)
    {
        $this->_reader = $reader;
        if ($paths) {
            $this->addPaths((array) $paths);
        }
    }

    /**
     * Append lookup paths to metadata driver.
     *
     * @param array $paths
     */
    public function addPaths(array $paths)
    {
        $this->_paths = array_unique(array_merge($this->_paths, $paths));
    }

    /**
     * Retrieve the defined metadata lookup paths.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->_paths;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $reflClass = $class->getReflectionClass();

        $classAnnotations = $this->_reader->getClassAnnotations($reflClass);
        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\Document'])) {
            $documentAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\Document'];
            if ($documentAnnot->db) {
                $class->setDB($documentAnnot->db);
            }
            if ($documentAnnot->collection) {
                $class->setCollection($documentAnnot->collection);
            }
            if ($documentAnnot->repositoryClass) {
                $class->setCustomRepositoryClass($documentAnnot->repositoryClass);
            }
            if ($documentAnnot->indexes) {
                foreach($documentAnnot->indexes as $index) {
                    $class->addIndex($index->keys, $index->options);
                }
            }
            if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\InheritanceType'])) {
                $inheritanceTypeAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\InheritanceType'];
                $class->setInheritanceType(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::INHERITANCE_TYPE_' . $inheritanceTypeAnnot->value));
            }
            if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\DiscriminatorField'])) {
                $discrFieldAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\DiscriminatorField'];
                $class->setDiscriminatorField(array(
                    'fieldName' => $discrFieldAnnot->fieldName,
                ));
            }
            if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\DiscriminatorMap'])) {
                $discrMapAnnot = $classAnnotations['Doctrine\ODM\MongoDB\Mapping\DiscriminatorMap'];
                $class->setDiscriminatorMap($discrMapAnnot->value);
            }
        } else if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\MappedSuperclass'])) {
            $class->isMappedSuperclass = true;
        } else if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\EmbeddedDocument'])) {
            $class->isEmbeddedDocument = true;
        }

        foreach ($reflClass->getProperties() as $property) {
            $mapping = array();
            $mapping['fieldName'] = $property->getName();
            
            $types = array(
                'Id', 'File', 'Field', 'String', 'Boolean', 'Int', 'Float', 'Date',
                'Key', 'Bin', 'BinFunc', 'BinUUID', 'BinMD5', 'BinCustom', 'EmbedOne',
                'EmbedMany', 'ReferenceOne', 'ReferenceMany', 'Timestamp', 'Hash'
            );
            foreach ($types as $type) {
                if ($fieldAnnot = $this->_reader->getPropertyAnnotation($property, 'Doctrine\ODM\MongoDB\Mapping\\' . $type)) {
                    $mapping = array_merge($mapping, (array) $fieldAnnot);
                    $class->mapField($mapping);
                    break;
                }
            }
        }

        if (isset($classAnnotations['Doctrine\ODM\MongoDB\Mapping\HasLifecycleCallbacks'])) {
            foreach ($reflClass->getMethods() as $method) {
                if ($method->isPublic()) {
                    $annotations = $this->_reader->getMethodAnnotations($method);

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\PrePersist'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::prePersist);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\PostPersist'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postPersist);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\PreUpdate'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::preUpdate);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\PostUpdate'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postUpdate);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\PreRemove'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::preRemove);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\PostRemove'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postRemove);
                    }

                    if (isset($annotations['Doctrine\ODM\MongoDB\Mapping\PostLoad'])) {
                        $class->addLifecycleCallback($method->getName(), \Doctrine\ODM\MongoDB\Events::postLoad);
                    }
                }
            }
        }
    }
}