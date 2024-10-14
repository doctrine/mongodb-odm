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
 *
 * @phpstan-import-type Hints from UnitOfWork
 */
final class HydratorFactory
{
    /**
     * The DocumentManager this factory is bound to.
     */
    private DocumentManager $dm;

    /**
     * The EventManager associated with this Hydrator
     */
    private EventManager $evm;

    /**
     * Which algorithm to use to automatically (re)generate hydrator classes.
     */
    private int $autoGenerate;

    /**
     * The namespace that contains all hydrator classes.
     */
    private ?string $hydratorNamespace;

    /**
     * The directory that contains all hydrator classes.
     */
    private string $hydratorDir;

    /**
     * Array of instantiated document hydrators.
     *
     * @var array<class-string, HydratorInterface>
     */
    private array $hydrators = [];

    /** @throws HydratorException */
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
     * Gets the hydrator object for the given document class.
     *
     * @param class-string $className
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

        $this->hydrators[$className] = new $fqn($this->dm, $class);

        return $this->hydrators[$className];
    }

    /**
     * Generates hydrator classes for all given classes.
     *
     * @param ClassMetadata<object>[] $classes The classes (ClassMetadata instances) for which to generate hydrators.
     * @param string|null             $toDir   The target directory of the hydrator classes. If not specified, the
     *                                    directory configured on the Configuration of the DocumentManager used
     *                                    by this factory is used.
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

    /** @param ClassMetadata<object> $class */
    private function generateHydratorClass(ClassMetadata $class, string $hydratorClassName, ?string $fileName): void
    {
        $code = '';

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            if (isset($mapping['alsoLoadFields'])) {
                foreach ($mapping['alsoLoadFields'] as $name) {
                    $code .= sprintf(
                        <<<EOF

        // AlsoLoad("$name")
        if (! array_key_exists('%1\$s', \$data) && array_key_exists('$name', \$data)) {
            \$data['%1\$s'] = \$data['$name'];
        }

EOF
                        ,
                        $mapping['name'],
                    );
                }
            }

            if ($mapping['type'] === 'date') {
                $code .= sprintf(
                    <<<'EOF'

        // Field(type: "date")
        if (isset($data['%1$s'])) {
            $value = $data['%1$s'];
            %3$s
            $this->class->reflFields['%2$s']->setValue($document, clone $return);
            $hydratedData['%2$s'] = $return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    Type::getType($mapping['type'])->closureToPHP(),
                );
            } elseif (! isset($mapping['association'])) {
                $code .= sprintf(
                    <<<EOF

        // Field(type: "{$mapping['type']}")
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
                    Type::getType($mapping['type'])->closureToPHP(),
                );
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isOwningSide']) {
                $code .= sprintf(
                    <<<'EOF'

        // ReferenceOne
        if (isset($data['%1$s']) || (! empty($this->class->fieldMappings['%2$s']['nullable']) && array_key_exists('%1$s', $data))) {
            $return = $data['%1$s'];
            if ($return !== null) {
                if ($this->class->fieldMappings['%2$s']['storeAs'] !== ClassMetadata::REFERENCE_STORE_AS_ID && ! is_array($return)) {
                    throw HydratorException::associationTypeMismatch('%3$s', '%1$s', 'array', gettype($return));
                }

                $className = $this->dm->getClassNameForAssociation($this->class->fieldMappings['%2$s'], $return);
                $identifier = ClassMetadata::getReferenceId($return, $this->class->fieldMappings['%2$s']['storeAs']);
                $targetMetadata = $this->dm->getClassMetadata($className);
                $id = $targetMetadata->getPHPIdentifierValue($identifier);
                $return = $this->dm->getReference($className, $id);
            }

            $this->class->reflFields['%2$s']->setValue($document, $return);
            $hydratedData['%2$s'] = $return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    $class->getName(),
                );
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isInverseSide']) {
                if (isset($mapping['repositoryMethod']) && $mapping['repositoryMethod']) {
                    $code .= sprintf(
                        <<<'EOF'

        $className = $this->class->fieldMappings['%2$s']['targetDocument'];
        $return = $this->dm->getRepository($className)->%3$s($document);
        $this->class->reflFields['%2$s']->setValue($document, $return);
        $hydratedData['%2$s'] = $return;

EOF
                        ,
                        $mapping['name'],
                        $mapping['fieldName'],
                        $mapping['repositoryMethod'],
                    );
                } else {
                    $code .= sprintf(
                        <<<'EOF'

        $mapping = $this->class->fieldMappings['%2$s'];
        $className = $mapping['targetDocument'];
        $targetClass = $this->dm->getClassMetadata($mapping['targetDocument']);
        $mappedByMapping = $targetClass->fieldMappings[$mapping['mappedBy']];
        $mappedByFieldName = ClassMetadata::getReferenceFieldName($mappedByMapping['storeAs'], $mapping['mappedBy']);
        $criteria = array_merge(
            [$mappedByFieldName => $data['_id']],
            $this->class->fieldMappings['%2$s']['criteria'] ?? []
        );
        $sort = $this->class->fieldMappings['%2$s']['sort'] ?? [];
        $return = $this->dm->getUnitOfWork()->getDocumentPersister($className)->load($criteria, null, [], 0, $sort);
        $this->class->reflFields['%2$s']->setValue($document, $return);
        $hydratedData['%2$s'] = $return;

EOF
                        ,
                        $mapping['name'],
                        $mapping['fieldName'],
                    );
                }
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_MANY || $mapping['association'] === ClassMetadata::EMBED_MANY) {
                $code .= sprintf(
                    <<<'EOF'

        // ReferenceMany & EmbedMany
        $mongoData = $data['%1$s'] ?? null;

        if ($mongoData !== null && ! is_array($mongoData)) {
            throw HydratorException::associationTypeMismatch('%3$s', '%1$s', 'array', gettype($mongoData));
        }

        $return = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create($this->dm, $this->class->fieldMappings['%2$s']);
        $return->setHints($hints);
        $return->setOwner($document, $this->class->fieldMappings['%2$s']);
        $return->setInitialized(false);
        if ($mongoData) {
            $return->setMongoData($mongoData);
        }
        $this->class->reflFields['%2$s']->setValue($document, $return);
        $hydratedData['%2$s'] = $return;

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    $class->getName(),
                );
            } elseif ($mapping['association'] === ClassMetadata::EMBED_ONE) {
                $code .= sprintf(
                    <<<'EOF'

        // EmbedOne
        if (isset($data['%1$s']) || (! empty($this->class->fieldMappings['%2$s']['nullable']) && array_key_exists('%1$s', $data))) {
            $return = $data['%1$s'];
            if ($return !== null) {
                $embeddedDocument = $return;

                if (! is_array($embeddedDocument)) {
                    throw HydratorException::associationTypeMismatch('%3$s', '%1$s', 'array', gettype($embeddedDocument));
                }
        
                $className = $this->dm->getClassNameForAssociation($this->class->fieldMappings['%2$s'], $embeddedDocument);
                $embeddedMetadata = $this->dm->getClassMetadata($className);
                $return = $embeddedMetadata->newInstance();

                $this->dm->getUnitOfWork()->setParentAssociation($return, $this->class->fieldMappings['%2$s'], $document, '%1$s');

                $embeddedData = $this->dm->getHydratorFactory()->hydrate($return, $embeddedDocument, $hints);
                $embeddedId = $embeddedMetadata->identifier && isset($embeddedData[$embeddedMetadata->identifier]) ? $embeddedData[$embeddedMetadata->identifier] : null;

                if (empty($hints[Query::HINT_READ_ONLY])) {
                    $this->dm->getUnitOfWork()->registerManaged($return, $embeddedId, $embeddedData);
                }
            }

            $this->class->reflFields['%2$s']->setValue($document, $return);
            $hydratedData['%2$s'] = $return;
        }

EOF
                    ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    $class->getName(),
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
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

use function array_key_exists;
use function gettype;
use function is_array;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class $hydratorClassName implements HydratorInterface
{
    public function __construct(private DocumentManager \$dm, private ClassMetadata \$class) {}

    public function hydrate(object \$document, array \$data, array \$hints = []): array
    {
        \$hydratedData = [];
%s        return \$hydratedData;
    }
}
EOF
            ,
            $code,
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
     * @param array<string, mixed> $data
     * @phpstan-param Hints $hints Any hints to account for during reconstitution/lookup of the document.
     *
     * @return array<string, mixed>
     */
    public function hydrate(object $document, array $data, array $hints = []): array
    {
        $metadata = $this->dm->getClassMetadata($document::class);
        // Invoke preLoad lifecycle events and listeners
        if (! empty($metadata->lifecycleCallbacks[Events::preLoad])) {
            $args = [new PreLoadEventArgs($document, $this->dm, $data)];
            $metadata->invokeLifecycleCallbacks(Events::preLoad, $document, $args);
        }

        $this->evm->dispatchEvent(Events::preLoad, new PreLoadEventArgs($document, $this->dm, $data));

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
                array $properties, // we currently do not use this
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

        $this->evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($document, $this->dm));

        return $data;
    }
}
