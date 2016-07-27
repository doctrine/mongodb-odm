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

namespace Doctrine\ODM\MongoDB\Hydrator;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Configuration;

/**
 * The HydratorFactory class is responsible for instantiating a correct hydrator
 * type based on document's ClassMetadata
 *
 * @since       1.0
 */
class HydratorFactory
{
    /**
     * The DocumentManager this factory is bound to.
     *
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var \Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $unitOfWork;

    /**
     * The EventManager associated with this Hydrator
     *
     * @var \Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * Which algorithm to use to automatically (re)generate hydrator classes.
     *
     * @var integer
     */
    private $autoGenerate;

    /**
     * The namespace that contains all hydrator classes.
     *
     * @var string
     */
    private $hydratorNamespace;

    /**
     * The directory that contains all hydrator classes.
     *
     * @var string
     */
    private $hydratorDir;

    /**
     * Array of instantiated document hydrators.
     *
     * @var array
     */
    private $hydrators = array();

    /**
     * @param DocumentManager $dm
     * @param EventManager $evm
     * @param string $hydratorDir
     * @param string $hydratorNs
     * @param integer $autoGenerate
     * @throws HydratorException
     */
    public function __construct(DocumentManager $dm, EventManager $evm, $hydratorDir, $hydratorNs, $autoGenerate)
    {
        if ( ! $hydratorDir) {
            throw HydratorException::hydratorDirectoryRequired();
        }
        if ( ! $hydratorNs) {
            throw HydratorException::hydratorNamespaceRequired();
        }
        $this->dm = $dm;
        $this->evm = $evm;
        $this->hydratorDir = $hydratorDir;
        $this->hydratorNamespace = $hydratorNs;
        $this->autoGenerate = $autoGenerate;
    }

    /**
     * Sets the UnitOfWork instance.
     *
     * @param UnitOfWork $uow
     */
    public function setUnitOfWork(UnitOfWork $uow)
    {
        $this->unitOfWork = $uow;
    }

    /**
     * Gets the hydrator object for the given document class.
     *
     * @param string $className
     * @return \Doctrine\ODM\MongoDB\Hydrator\HydratorInterface $hydrator
     */
    public function getHydratorFor($className)
    {
        if (isset($this->hydrators[$className])) {
            return $this->hydrators[$className];
        }
        $hydratorClassName = str_replace('\\', '', $className) . 'Hydrator';
        $fqn = $this->hydratorNamespace . '\\' . $hydratorClassName;
        $class = $this->dm->getClassMetadata($className);

        if ( ! class_exists($fqn, false)) {
            $fileName = $this->hydratorDir . DIRECTORY_SEPARATOR . $hydratorClassName . '.php';
            switch ($this->autoGenerate) {
                case Configuration::AUTOGENERATE_NEVER:
                    require $fileName;
                    break;
                    
                case Configuration::AUTOGENERATE_ALWAYS:
                    $this->generateHydratorClass($class, $hydratorClassName, $fileName);
                    require $fileName;
                    break;
                    
                case Configuration::AUTOGENERATE_FILE_NOT_EXISTS:
                    if (!file_exists($fileName)) {
                        $this->generateHydratorClass($class, $hydratorClassName, $fileName);
                    }
                    require $fileName;
                    break;
                    
                case Configuration::AUTOGENERATE_EVAL:
                    $this->generateHydratorClass($class, $hydratorClassName, false);
                    break;
            }
        }
        $this->hydrators[$className] = new $fqn($this->dm, $this->unitOfWork, $class);
        return $this->hydrators[$className];
    }

    /**
     * Generates hydrator classes for all given classes.
     *
     * @param array $classes The classes (ClassMetadata instances) for which to generate hydrators.
     * @param string $toDir The target directory of the hydrator classes. If not specified, the
     *                      directory configured on the Configuration of the DocumentManager used
     *                      by this factory is used.
     */
    public function generateHydratorClasses(array $classes, $toDir = null)
    {
        $hydratorDir = $toDir ?: $this->hydratorDir;
        $hydratorDir = rtrim($hydratorDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        foreach ($classes as $class) {
            $hydratorClassName = str_replace('\\', '', $class->name) . 'Hydrator';
            $hydratorFileName = $hydratorDir . $hydratorClassName . '.php';
            $this->generateHydratorClass($class, $hydratorClassName, $hydratorFileName);
        }
    }

    /**
     * @param ClassMetadata $class
     * @param string $hydratorClassName
     * @param string $fileName
     */
    private function generateHydratorClass(ClassMetadata $class, $hydratorClassName, $fileName)
    {
        $code = '';

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if (isset($mapping['alsoLoadFields'])) {
                foreach ($mapping['alsoLoadFields'] as $name) {
                    $code .= sprintf(<<<EOF

        /** @AlsoLoad("$name") */
        if (!array_key_exists('%1\$s', \$data) && array_key_exists('$name', \$data)) {
            \$data['%1\$s'] = \$data['$name'];
        }

EOF
                        ,
                        $mapping['name']
                    );
                }
            }

            if ($mapping['type'] === 'date') {
                $code .= sprintf(<<<EOF

        /** @Field(type="date") */
        if (isset(\$data['%1\$s'])) {
            \$value = \$data['%1\$s'];
            %3\$s
            \$this->class->reflFields['%2\$s']->setValue(\$document, clone \$return);
            \$hydratedData['%2\$s'] = \$return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    Type::getType($mapping['type'])->closureToPHP()
                );


            } elseif ( ! isset($mapping['association'])) {
                $code .= sprintf(<<<EOF

        /** @Field(type="{$mapping['type']}") */
        if (isset(\$data['%1\$s']) || (! empty(\$this->class->fieldMappings['%2\$s']['nullable']) && array_key_exists('%1\$s', \$data))) {
            \$value = \$data['%1\$s'];
            if (\$value !== null) {
                %3\$s
            } else {
                \$return = null;
            }
            \$this->class->reflFields['%2\$s']->setValue(\$document, \$return);
            \$hydratedData['%2\$s'] = \$return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    Type::getType($mapping['type'])->closureToPHP()
                );
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isOwningSide']) {
                $code .= sprintf(<<<EOF

        /** @ReferenceOne */
        if (isset(\$data['%1\$s'])) {
            \$reference = \$data['%1\$s'];
            if (isset(\$this->class->fieldMappings['%2\$s']['storeAs']) && \$this->class->fieldMappings['%2\$s']['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID) {
                \$className = \$this->class->fieldMappings['%2\$s']['targetDocument'];
                \$mongoId = \$reference;
            } else {
                \$className = \$this->unitOfWork->getClassNameForAssociation(\$this->class->fieldMappings['%2\$s'], \$reference);
                \$mongoId = \$reference['\$id'];
            }
            \$targetMetadata = \$this->dm->getClassMetadata(\$className);
            \$id = \$targetMetadata->getPHPIdentifierValue(\$mongoId);
            \$return = \$this->dm->getReference(\$className, \$id);
            \$this->class->reflFields['%2\$s']->setValue(\$document, \$return);
            \$hydratedData['%2\$s'] = \$return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName']
                );
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isInverseSide']) {
                if (isset($mapping['repositoryMethod']) && $mapping['repositoryMethod']) {
                    $code .= sprintf(<<<EOF

        \$className = \$this->class->fieldMappings['%2\$s']['targetDocument'];
        \$return = \$this->dm->getRepository(\$className)->%3\$s(\$document);
        \$this->class->reflFields['%2\$s']->setValue(\$document, \$return);
        \$hydratedData['%2\$s'] = \$return;

EOF
                        ,
                        $mapping['name'],
                        $mapping['fieldName'],
                        $mapping['repositoryMethod']
                    );
                } else {
                    $code .= sprintf(<<<EOF

        \$mapping = \$this->class->fieldMappings['%2\$s'];
        \$className = \$mapping['targetDocument'];
        \$targetClass = \$this->dm->getClassMetadata(\$mapping['targetDocument']);
        \$mappedByMapping = \$targetClass->fieldMappings[\$mapping['mappedBy']];
        \$mappedByFieldName = isset(\$mappedByMapping['storeAs']) && \$mappedByMapping['storeAs'] === ClassMetadataInfo::REFERENCE_STORE_AS_ID ? \$mapping['mappedBy'] : \$mapping['mappedBy'].'.\$id';
        \$criteria = array_merge(
            array(\$mappedByFieldName => \$data['_id']),
            isset(\$this->class->fieldMappings['%2\$s']['criteria']) ? \$this->class->fieldMappings['%2\$s']['criteria'] : array()
        );
        \$sort = isset(\$this->class->fieldMappings['%2\$s']['sort']) ? \$this->class->fieldMappings['%2\$s']['sort'] : array();
        \$return = \$this->unitOfWork->getDocumentPersister(\$className)->load(\$criteria, null, array(), 0, \$sort);
        \$this->class->reflFields['%2\$s']->setValue(\$document, \$return);
        \$hydratedData['%2\$s'] = \$return;

EOF
                        ,
                        $mapping['name'],
                        $mapping['fieldName']
                    );
                }
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_MANY || $mapping['association'] === ClassMetadata::EMBED_MANY) {
                $code .= sprintf(<<<EOF

        /** @Many */
        \$mongoData = isset(\$data['%1\$s']) ? \$data['%1\$s'] : null;
        \$return = \$this->dm->getConfiguration()->getPersistentCollectionFactory()->create(\$this->dm, \$this->class->fieldMappings['%2\$s']);
        \$return->setHints(\$hints);
        \$return->setOwner(\$document, \$this->class->fieldMappings['%2\$s']);
        \$return->setInitialized(false);
        if (\$mongoData) {
            \$return->setMongoData(\$mongoData);
        }
        \$this->class->reflFields['%2\$s']->setValue(\$document, \$return);
        \$hydratedData['%2\$s'] = \$return;

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName']
                );
            } elseif ($mapping['association'] === ClassMetadata::EMBED_ONE) {
                $code .= sprintf(<<<EOF

        /** @EmbedOne */
        if (isset(\$data['%1\$s'])) {
            \$embeddedDocument = \$data['%1\$s'];
            \$className = \$this->unitOfWork->getClassNameForAssociation(\$this->class->fieldMappings['%2\$s'], \$embeddedDocument);
            \$embeddedMetadata = \$this->dm->getClassMetadata(\$className);
            \$return = \$embeddedMetadata->newInstance();

            \$this->unitOfWork->setParentAssociation(\$return, \$this->class->fieldMappings['%2\$s'], \$document, '%1\$s');

            \$embeddedData = \$this->dm->getHydratorFactory()->hydrate(\$return, \$embeddedDocument, \$hints);
            \$embeddedId = \$embeddedMetadata->identifier && isset(\$embeddedData[\$embeddedMetadata->identifier]) ? \$embeddedData[\$embeddedMetadata->identifier] : null;

            if (empty(\$hints[Query::HINT_READ_ONLY])) {
                \$this->unitOfWork->registerManaged(\$return, \$embeddedId, \$embeddedData);
            }

            \$this->class->reflFields['%2\$s']->setValue(\$document, \$return);
            \$hydratedData['%2\$s'] = \$return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName']
                );
            }
        }

        $namespace = $this->hydratorNamespace;
        $code = sprintf(<<<EOF
<?php

namespace $namespace;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class $hydratorClassName implements HydratorInterface
{
    private \$dm;
    private \$unitOfWork;
    private \$class;

    public function __construct(DocumentManager \$dm, UnitOfWork \$uow, ClassMetadata \$class)
    {
        \$this->dm = \$dm;
        \$this->unitOfWork = \$uow;
        \$this->class = \$class;
    }

    public function hydrate(\$document, \$data, array \$hints = array())
    {
        \$hydratedData = array();
%s        return \$hydratedData;
    }
}
EOF
            ,
            $code
        );

        if ($fileName === false) {
            if ( ! class_exists($namespace . '\\' . $hydratorClassName)) {
                eval(substr($code, 5));
            }
        } else {
            $parentDirectory = dirname($fileName);

            if ( ! is_dir($parentDirectory) && (false === @mkdir($parentDirectory, 0775, true))) {
                throw HydratorException::hydratorDirectoryNotWritable();
            }

            if ( ! is_writable($parentDirectory)) {
                throw HydratorException::hydratorDirectoryNotWritable();
            }

            $tmpFileName = $fileName . '.' . uniqid('', true);
            file_put_contents($tmpFileName, $code);
            rename($tmpFileName, $fileName);
            chmod($fileName, 0664);
        }
    }

    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param object $document  The document object to hydrate the data into.
     * @param array $data The array of document data.
     * @param array $hints Any hints to account for during reconstitution/lookup of the document.
     * @return array $values The array of hydrated values.
     */
    public function hydrate($document, $data, array $hints = array())
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        // Invoke preLoad lifecycle events and listeners
        if ( ! empty($metadata->lifecycleCallbacks[Events::preLoad])) {
            $args = array(new PreLoadEventArgs($document, $this->dm, $data));
            $metadata->invokeLifecycleCallbacks(Events::preLoad, $document, $args);
        }
        if ($this->evm->hasListeners(Events::preLoad)) {
            $this->evm->dispatchEvent(Events::preLoad, new PreLoadEventArgs($document, $this->dm, $data));
        }

        // alsoLoadMethods may transform the document before hydration
        if ( ! empty($metadata->alsoLoadMethods)) {
            foreach ($metadata->alsoLoadMethods as $method => $fieldNames) {
                foreach ($fieldNames as $fieldName) {
                    // Invoke the method only once for the first field we find
                    if (array_key_exists($fieldName, $data)) {
                        $document->$method($data[$fieldName]);
                        continue 2;
                    }
                }
            }
        }

        $data = $this->getHydratorFor($metadata->name)->hydrate($document, $data, $hints);
        if ($document instanceof Proxy) {
            $document->__isInitialized__ = true;
            $document->__setInitializer(null);
            $document->__setCloner(null);
            // lazy properties may be left uninitialized
            $properties = $document->__getLazyProperties();
            foreach ($properties as $propertyName => $property) {
                if ( ! isset($document->$propertyName)) {
                    $document->$propertyName = $properties[$propertyName];
                }
            }
        }

        // Invoke the postLoad lifecycle callbacks and listeners
        if ( ! empty($metadata->lifecycleCallbacks[Events::postLoad])) {
            $metadata->invokeLifecycleCallbacks(Events::postLoad, $document, array(new LifecycleEventArgs($document, $this->dm)));
        }
        if ($this->evm->hasListeners(Events::postLoad)) {
            $this->evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($document, $this->dm));
        }

        return $data;
    }
}
