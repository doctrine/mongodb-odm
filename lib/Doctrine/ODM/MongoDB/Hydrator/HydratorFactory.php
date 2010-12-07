<?php

namespace Doctrine\ODM\MongoDB\Hydrator;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Types\Type;

class HydratorFactory
{
    /** The DocumentManager this factory is bound to. */
    private $dm;

    /** Whether to automatically (re)generate hydrator classes. */
    private $autoGenerate;

    /** The namespace that contains all hydrator classes. */
    private $hydratorNamespace;

    /** The directory that contains all hydrator classes. */
    private $hydratorDir;

    /** Array of instantiated document hydrators */
    private $hydrators = array();

    public function __construct(DocumentManager $dm, $hydratorDir, $hydratorNs, $autoGenerate = false)
    {
        if ( ! $hydratorDir) {
            throw HydratorException::hydratorDirectoryRequired();
        }
        if ( ! $hydratorNs) {
            throw HydratorException::hydratorNamespaceRequired();
        }
        $this->dm = $dm;
        $this->hydratorDir = $hydratorDir;
        $this->autoGenerate = $autoGenerate;
        $this->hydratorNamespace = $hydratorNs;
    }

    /**
     * Gets the hydrator object for the given document class.
     *
     * @param string $className
     * @return Doctrine\ODM\MongoDB\Hydrator\HydratorInterface $hydrator
     */
    public function getHydratorFor($className)
    {
        if (isset($this->hydrators[$className])) {
            return $this->hydrators[$className];
        }
        $hydratorClassName = str_replace('\\', '', $className) . 'Hydrator';
        $fqn = $this->hydratorNamespace . '\\' . $hydratorClassName;
        $class = $this->dm->getClassMetadata($className);

        if (! class_exists($fqn, false)) {
            $fileName = $this->hydratorDir . DIRECTORY_SEPARATOR . $hydratorClassName . '.php';
            if ($this->autoGenerate) {
                $this->generateHydratorClass($class, $hydratorClassName, $fileName);
            }
            require $fileName;
        }
        $this->hydrators[$className] = new $fqn($this->dm, $class);
        return $this->hydrators[$className];
    }

    private function generateHydratorClass(ClassMetadata $class, $hydratorClassName, $fileName)
    {
        $code = '';

        foreach ($class->fieldMappings as $fieldName => $mapping) {
            $unsetCode = null;
            if ($mapping['name'] !== $mapping['fieldName']) {
                $unsetCode = sprintf(<<<EOF

        unset(\$data['%1\$s']);
EOF
                ,
                    $mapping['name'],
                    $mapping['fieldName']
                );
            }
            $unsetCode = <<<EOF

            \$data['$fieldName'] = \$return;
EOF;

            if (isset($mapping['alsoLoadFields'])) {
                foreach ($mapping['alsoLoadFields'] as $name) {
                    $code .= sprintf(<<<EOF

        if (isset(\$data['$name'])) {
            \$data['$fieldName'] = \$data['$name'];
        }
EOF
                    );
                }
            }

            if ( ! isset($mapping['association'])) {
                $code .= sprintf(<<<EOF

        if (isset(\$data['%1\$s'])) {
            \$value = \$data['%1\$s'];
            %3\$s
            \$this->class->reflFields['$fieldName']->setValue(\$document, \$return);%4\$s
        }

EOF
                ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    Type::getType($mapping['type'])->closureToPHP(),
                    $unsetCode
                );
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                $code .= sprintf(<<<EOF
        if (isset(\$data['%1\$s'])) {
            \$reference = \$data['%1\$s'];
            \$className = \$this->dm->getClassNameFromDiscriminatorValue(\$this->class->fieldMappings['%2\$s'], \$reference);
            \$targetMetadata = \$this->dm->getClassMetadata(\$className);
            \$id = \$targetMetadata->getPHPIdentifierValue(\$reference['\$id']);
            \$return = \$this->dm->getReference(\$className, \$id);
            \$this->class->reflFields['$fieldName']->setValue(\$document, \$return);%3\$s
        }


EOF
                ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    $unsetCode
                );
            } elseif ($mapping['association'] === ClassMetadata::REFERENCE_MANY || $mapping['association'] === ClassMetadata::EMBED_MANY) {
                $code .= sprintf(<<<EOF
        \$mongoData = isset(\$data['%1\$s']) ? \$data['%1\$s'] : null;
        \$return = new \Doctrine\ODM\MongoDB\PersistentCollection(new \Doctrine\Common\Collections\ArrayCollection(), \$this->dm, \$this->dm->getUnitOfWork(), '$');
        \$return->setOwner(\$document, \$this->class->fieldMappings['%2\$s']);
        \$return->setInitialized(false);
        if (\$mongoData) {
            \$return->setMongoData(\$mongoData);
        }
        \$this->class->reflFields['$fieldName']->setValue(\$document, \$return);%3\$s


EOF
                ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    $unsetCode
                );
            } elseif ($mapping['association'] === ClassMetadata::EMBED_ONE) {
                $code .= sprintf(<<<EOF
        if (isset(\$data['%1\$s'])) {
            \$embeddedDocument = \$data['%1\$s'];
            \$className = \$this->dm->getClassNameFromDiscriminatorValue(\$this->class->fieldMappings['%2\$s'], \$embeddedDocument);
            \$embeddedMetadata = \$this->dm->getClassMetadata(\$className);
            \$return = \$embeddedMetadata->newInstance();

            \$embeddedData = \$this->dm->getHydrator()->hydrate(\$return, \$embeddedDocument);
            \$this->dm->getUnitOfWork()->registerManaged(\$return, null, \$embeddedData);
            \$this->dm->getUnitOfWork()->setParentAssociation(\$return, \$this->class->fieldMappings['%2\$s'], \$document, '%1\$s');

            \$this->class->reflFields['$fieldName']->setValue(\$document, \$return);%3\$s
        }


EOF
                ,
                    $mapping['name'],
                    $mapping['fieldName'],
                    $unsetCode
                );
            }
        }

        $className = $class->name;
        $namespace = $this->hydratorNamespace;
        $code = sprintf(<<<EOF
<?php

namespace $namespace;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class $hydratorClassName implements HydratorInterface
{
    private \$dm;
    private \$class;

    public function __construct(DocumentManager \$dm, ClassMetadata \$class)
    {
        \$this->dm = \$dm;
        \$this->class = \$class;
    }

    public function hydrate(\$document, \$data)
    {
%s        return \$data;
    }
}
EOF
          ,
          $code
        );

        file_put_contents($fileName, $code);
    }
}