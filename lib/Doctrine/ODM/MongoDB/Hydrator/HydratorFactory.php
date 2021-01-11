<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Hydrator;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;
use ProxyManager\Proxy\GhostObjectInterface;

use function array_key_exists;
use function chmod;
use function class_exists;
use function dirname;
use function file_exists;
use function file_put_contents;
use function get_class;
use function is_dir;
use function is_writable;
use function mkdir;
use function rename;
use function rtrim;
use function sprintf;
use function str_replace;
use function substr;
use function uniqid;

use const DIRECTORY_SEPARATOR;

/**
 * The HydratorFactory class is responsible for instantiating a correct hydrator
 * type based on document's ClassMetadata
 */
final class HydratorFactory
{
    /**
     * The DocumentManager this factory is bound to.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork used to coordinate object-level transactions.
     *
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * The EventManager associated with this Hydrator
     *
     * @var EventManager
     */
    private $evm;

    /**
     * Which algorithm to use to automatically (re)generate hydrator classes.
     *
     * @var int
     */
    private $autoGenerate;

    /**
     * The namespace that contains all hydrator classes.
     *
     * @var string|null
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
    private $hydrators = [];

    /**
     * @throws HydratorException
     */
    public function __construct(DocumentManager $dm, EventManager $evm, ?string $hydratorDir, ?string $hydratorNs, int $autoGenerate)
    {
        if (! $hydratorDir) {
            throw HydratorException::hydratorDirectoryRequired();
        }

        if (! $hydratorNs) {
            throw HydratorException::hydratorNamespaceRequired();
        }

        $this->dm                = $dm;
        $this->evm               = $evm;
        $this->hydratorDir       = $hydratorDir;
        $this->hydratorNamespace = $hydratorNs;
        $this->autoGenerate      = $autoGenerate;
    }

    /**
     * Sets the UnitOfWork instance.
     *
     * @internal
     */
    public function setUnitOfWork(UnitOfWork $uow): void
    {
        $this->unitOfWork = $uow;
    }

    /**
     * Gets the hydrator object for the given document class.
     */
    public function getHydratorFor(string $className): HydratorInterface
    {
        if (isset($this->hydrators[$className])) {
            return $this->hydrators[$className];
        }

        $hydratorClassName = str_replace('\\', '', $className) . 'Hydrator';
        $fqn               = $this->hydratorNamespace . '\\' . $hydratorClassName;
        $class             = $this->dm->getClassMetadata($className);

        if (! class_exists($fqn, false)) {
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
                    if (! file_exists($fileName)) {
                        $this->generateHydratorClass($class, $hydratorClassName, $fileName);
                    }

                    require $fileName;
                    break;

                case Configuration::AUTOGENERATE_EVAL:
                    $this->generateHydratorClass($class, $hydratorClassName, null);
                    break;
            }
        }

        $this->hydrators[$className] = new $fqn($this->dm, $this->unitOfWork, $class);

        return $this->hydrators[$className];
    }

    /**
     * Generates hydrator classes for all given classes.
     *
     * @param array  $classes The classes (ClassMetadata instances) for which to generate hydrators.
     * @param string $toDir   The target directory of the hydrator classes. If not specified, the
     *                        directory configured on the Configuration of the DocumentManager used
     *                        by this factory is used.
     */
    public function generateHydratorClasses(array $classes, ?string $toDir = null): void
    {
        $hydratorDir = $toDir ?: $this->hydratorDir;
        $hydratorDir = rtrim($hydratorDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        foreach ($classes as $class) {
            $hydratorClassName = str_replace('\\', '', $class->name) . 'Hydrator';
            $hydratorFileName  = $hydratorDir . $hydratorClassName . '.php';
            $this->generateHydratorClass($class, $hydratorClassName, $hydratorFileName);
        }
    }

    private function generateHydratorClass(ClassMetadata $class, string $hydratorClassName, ?string $fileName): void
    {
        $code = '';

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if (isset($mapping['alsoLoadFields'])) {
                foreach ($mapping['alsoLoadFields'] as $name) {
                    $code .= sprintf(
                        <<<EOF

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
                $code .= sprintf(
                    <<<EOF

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
            } elseif (! isset($mapping['association'])) {
                $code .= sprintf(
                    <<<EOF

        /** @Field(type="{$mapping['type']}") */
        if (isset(\$data['%1\$s']) || (! empty(\$this->class->fieldMappings['%2\$s']['nullable']) && array_key_exists('%1\$s', \$data))) {
            \$value = \$data['%1\$s'];
            if (\$value !== null) {
                \$typeIdentifier = \$this->class->fieldMappings['%2\$s']['type'];
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
                $code .= sprintf(
                    <<<EOF

        /** @ReferenceOne */
        if (isset(\$data['%1\$s'])) {
            \$reference = \$data['%1\$s'];

            if (\$this->class->fieldMappings['%2\$s']['storeAs'] !== ClassMetadata::REFERENCE_STORE_AS_ID && ! is_array(\$reference)) {
                throw HydratorException::associationTypeMismatch('%3\$s', '%1\$s', 'array', gettype(\$reference));
            }

            \$className = \$this->unitOfWork->getClassNameForAssociation(\$this->class->fieldMappings['%2\$s'], \$reference);
            \$identifier = ClassMetadata::getReferenceId(\$reference, \$this->class->fieldMappings['%2\$s']['storeAs']);
            \$targetMetadata = \$this->dm->getClassMetadata(\$className);
            \$id = \$targetMetadata->getPHPIdentifierValue(\$identifier);
            \$return = \$this->dm->getReference(\$className, \$id);
            \$this->class->reflFields['%2\$s']->setValue(\$document, \$return);
            \$hydratedData['%2\$s'] = \$return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    $class->getName()
                );
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isInverseSide']) {
                if (isset($mapping['repositoryMethod']) && $mapping['repositoryMethod']) {
                    $code .= sprintf(
                        <<<EOF

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
                    $code .= sprintf(
                        <<<EOF

        \$mapping = \$this->class->fieldMappings['%2\$s'];
        \$className = \$mapping['targetDocument'];
        \$targetClass = \$this->dm->getClassMetadata(\$mapping['targetDocument']);
        \$mappedByMapping = \$targetClass->fieldMappings[\$mapping['mappedBy']];
        \$mappedByFieldName = ClassMetadata::getReferenceFieldName(\$mappedByMapping['storeAs'], \$mapping['mappedBy']);
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
                $code .= sprintf(
                    <<<EOF

        /** @Many */
        \$mongoData = isset(\$data['%1\$s']) ? \$data['%1\$s'] : null;

        if (\$mongoData !== null && ! is_array(\$mongoData)) {
            throw HydratorException::associationTypeMismatch('%3\$s', '%1\$s', 'array', gettype(\$mongoData));
        }

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
                    $mapping['fieldName'],
                    $class->getName()
                );
            } elseif ($mapping['association'] === ClassMetadata::EMBED_ONE) {
                $code .= sprintf(
                    <<<EOF

        /** @EmbedOne */
        if (isset(\$data['%1\$s'])) {
            \$embeddedDocument = \$data['%1\$s'];

            if (! is_array(\$embeddedDocument)) {
                throw HydratorException::associationTypeMismatch('%3\$s', '%1\$s', 'array', gettype(\$embeddedDocument));
            }
        
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
                    $mapping['fieldName'],
                    $class->getName()
                );
            }
        }

        $namespace = $this->hydratorNamespace;
        $code      = sprintf(
            <<<EOF
<?php

namespace $namespace;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorException;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\Query\Query;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

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

    public function hydrate(object \$document, array \$data, array \$hints = array()): array
    {
        \$hydratedData = array();
%s        return \$hydratedData;
    }
}
EOF
            ,
            $code
        );

        if ($fileName === null) {
            if (! class_exists($namespace . '\\' . $hydratorClassName)) {
                eval(substr($code, 5));
            }

            return;
        }

        $parentDirectory = dirname($fileName);

        if (! is_dir($parentDirectory) && (@mkdir($parentDirectory, 0775, true) === false)) {
            throw HydratorException::hydratorDirectoryNotWritable();
        }

        if (! is_writable($parentDirectory)) {
            throw HydratorException::hydratorDirectoryNotWritable();
        }

        $tmpFileName = $fileName . '.' . uniqid('', true);
        file_put_contents($tmpFileName, $code);
        rename($tmpFileName, $fileName);
        chmod($fileName, 0664);
    }

    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param array $hints Any hints to account for during reconstitution/lookup of the document.
     */
    public function hydrate(object $document, array $data, array $hints = []): array
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));
        // Invoke preLoad lifecycle events and listeners
        if (! empty($metadata->lifecycleCallbacks[Events::preLoad])) {
            $args = [new PreLoadEventArgs($document, $this->dm, $data)];
            $metadata->invokeLifecycleCallbacks(Events::preLoad, $document, $args);
        }

        if ($this->evm->hasListeners(Events::preLoad)) {
            $this->evm->dispatchEvent(Events::preLoad, new PreLoadEventArgs($document, $this->dm, $data));
        }

        // alsoLoadMethods may transform the document before hydration
        if (! empty($metadata->alsoLoadMethods)) {
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

        if ($document instanceof GhostObjectInterface && $document->getProxyInitializer() !== null) {
            // Inject an empty initialiser to not load any object data
            $document->setProxyInitializer(static function (
                GhostObjectInterface $ghostObject,
                string $method, // we don't care
                array $parameters, // we don't care
                &$initializer,
                array $properties // we currently do not use this
            ): bool {
                $initializer = null;

                return true;
            });
        }

        $data = $this->getHydratorFor($metadata->name)->hydrate($document, $data, $hints);

        // Invoke the postLoad lifecycle callbacks and listeners
        if (! empty($metadata->lifecycleCallbacks[Events::postLoad])) {
            $metadata->invokeLifecycleCallbacks(Events::postLoad, $document, [new LifecycleEventArgs($document, $this->dm)]);
        }

        if ($this->evm->hasListeners(Events::postLoad)) {
            $this->evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($document, $this->dm));
        }

        return $data;
    }
}
