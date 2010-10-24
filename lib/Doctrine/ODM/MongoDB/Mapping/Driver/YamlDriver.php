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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * The YamlDriver reads the mapping metadata from yaml schema files.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class YamlDriver extends AbstractFileDriver
{
    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $fileExtension = '.dcm.yml';

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        $element = $this->getElement($className);
        if ( ! $element) {
            return;
        }
        $element['type'] = isset($element['type']) ? $element['type'] : 'document';

        if (isset($element['db'])) {
            $class->setDB($element['db']);
        }
        if (isset($element['collection'])) {
            $class->setCollection($element['collection']);
        }
        if ($element['type'] == 'document') {
            if (isset($element['repositoryClass'])) {
                $class->setCustomRepositoryClass($element['repositoryClass']);
            }
        } elseif ($element['type'] === 'mappedSuperclass') {
            $class->isMappedSuperclass = true;
        } elseif ($element['type'] === 'embeddedDocument') {
            $class->isEmbeddedDocument = true;
        }
        if (isset($element['indexes'])) {
            foreach($element['indexes'] as $index) {
                $class->addIndex($index['keys'], isset($index['options']) ? $index['options'] : array());
            }
        }
        if (isset($element['inheritanceType'])) {
            $class->setInheritanceType(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::INHERITANCE_TYPE_' . strtoupper($element['inheritanceType'])));
        }
        if (isset($element['customId']) && $element['customId']) {
            $class->setAllowCustomId(true);
        }
        if (isset($element['discriminatorField'])) {
            $discrField = $element['discriminatorField'];
            $class->setDiscriminatorField(array(
                'name' => $discrField['name'],
                'fieldName' => $discrField['fieldName']
            ));
        }
        if (isset($element['discriminatorMap'])) {
            $class->setDiscriminatorMap($element['discriminatorMap']);
        }
        if (isset($element['changeTrackingPolicy'])) {
            $class->setChangeTrackingPolicy(constant('Doctrine\ODM\MongoDB\Mapping\ClassMetadata::CHANGETRACKING_'
                    . strtoupper($element['changeTrackingPolicy'])));
        }
        if (isset($element['fields'])) {
            foreach ($element['fields'] as $fieldName => $mapping) {
                if (is_string($mapping)) {
                    $type = $mapping;
                    $mapping = array();
                    $mapping['type'] = $type;
                }
                if ( ! isset($mapping['fieldName'])) {
                    $mapping['fieldName'] = $fieldName;
                }
                if (isset($mapping['type']) && $mapping['type'] === 'collection') {
                    $mapping['strategy'] = isset($mapping['strategy']) ? $mapping['strategy'] : 'pushPull';
                }
                $this->addFieldMapping($class, $mapping);
            }
        }
        if (isset($element['embedOne'])) {
            foreach ($element['embedOne'] as $fieldName => $embed) {
                $this->addMappingFromEmbed($class, $fieldName, $embed, 'one');
            }
        }
        if (isset($element['embedMany'])) {
            foreach ($element['embedMany'] as $fieldName => $embed) {
                $this->addMappingFromEmbed($class, $fieldName, $embed, 'many');
            }
        }
        if (isset($element['referenceOne'])) {
            foreach ($element['referenceOne'] as $fieldName => $reference) {
                $this->addMappingFromReference($class, $fieldName, $reference, 'one');
            }
        }
        if (isset($element['referenceMany'])) {
            foreach ($element['referenceMany'] as $fieldName => $reference) {
                $this->addMappingFromReference($class, $fieldName, $reference, 'many');
            }
        }
        if (isset($element['lifecycleCallbacks'])) {
            foreach ($element['lifecycleCallbacks'] as $type => $methods) {
                foreach ($methods as $method) {
                    $class->addLifecycleCallback($method, constant('Doctrine\ODM\MongoDB\ODMEvents::' . $type));
                }
            }
        }
    }

    private function addFieldMapping(ClassMetadata $class, $mapping)
    {
        $keys = null;
        $name = isset($mapping['name']) ? $mapping['name'] : $mapping['fieldName'];
        if (isset($mapping['index'])) {
            $keys = array(
                $name => isset($mapping['index']['order']) ? $mapping['index']['order'] : 'asc'
            );
        }
        if (isset($mapping['unique'])) {
            $keys = array(
                $name => isset($mapping['unique']['order']) ? $mapping['unique']['order'] : 'asc'
            );
        }
        if ($keys !== null) {
            $options = array();
            if (isset($mapping['index'])) {
                $options = $mapping['index'];
            } elseif (isset($mapping['unique'])) {
                $options = $mapping['unique'];
                $options['unique'] = true;
            }
            $class->addIndex($keys, $options);
        }
        $class->mapField($mapping);
    }

    private function addMappingFromDocument(ClassMetadata $class, $fieldName, $yml, $type, $embedRef)
    {
        $mapping = array(
            'type'                  => $type,
            'embedded'              => ($embedRef == 'embedded') ? true : null,
            'reference'             => ($embedRef == 'reference') ? true : null,
            'targetDocument'        => isset($yml['targetDocument']) ? $yml['targetDocument'] : null,
            'fieldName'             => $fieldName,
            'strategy'              => isset($yml['strategy']) ? (string) $yml['strategy'] : 'pushPull',
            'discriminatorField'    => isset($yml['discriminatorField']) ? $yml['discriminatorField'] : null,
            'discriminatorMapping'  => isset($yml['discriminatorMapping']) ? $yml['discriminatorMapping'] : null,
        );
        $this->addFieldMapping($class, $mapping);
    }

    private function addMappingFromEmbed(ClassMetadata $class, $fieldName, $embed, $type)
    {
        $this->addMappingFromDocument($class, $fieldName, $embed, $type, 'embedded');
    }

    private function addMappingFromReference(ClassMetadata $class, $fieldName, $reference, $type)
    {
        $this->addMappingFromDocument($class, $fieldName, $reference, $type, 'reference');
    }

    protected function loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::load($file);
    }
}