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

namespace Doctrine\ODM\MongoDB\PersistentCollection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionFactory;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * Default factory class for persistent collection classes.
 *
 * @since  1.1
 * @author Maciej Malarz <malarzm@gmail.com>
 */
final class DefaultPersistentCollectionFactory implements PersistentCollectionFactory
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
    private $uow;

    /**
     * Which algorithm to use to automatically (re)generate persistent collection classes.
     *
     * @var integer
     */
    private $autoGenerate;

    /**
     * The namespace that contains all persistent collection classes.
     *
     * @var string
     */
    private $collectionNamespace;

    /**
     * The directory that contains all persistent collection classes.
     *
     * @var string
     */
    private $collectionDir;

    /**
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Doctrine\ODM\MongoDB\UnitOfWork $uow
     * @param string $collectionDir
     * @param string $collectionNs
     * @param int $autoGenerate
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, $collectionDir, $collectionNs, $autoGenerate)
    {
        $this->dm = $dm;
        $this->uow = $uow;
        $this->collectionDir = $collectionDir;
        $this->collectionNamespace = $collectionNs;
        $this->autoGenerate = $autoGenerate;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $mapping, BaseCollection $coll = null)
    {
        if ($coll === null) {
            $coll = ! empty($mapping['collectionClass'])
                    ? new $mapping['collectionClass']
                    : new ArrayCollection();
        }
        if (empty($mapping['collectionClass'])) {
            return new PersistentCollection($coll, $this->dm, $this->uow);
        }

        // These checks are not in __construct() because of BC and should be moved for 2.0
        if ( ! $this->collectionDir) {
            throw PersistentCollectionException::directoryRequired();
        }
        if ( ! $this->collectionNamespace) {
            throw PersistentCollectionException::namespaceRequired();
        }

        $collClassName = str_replace('\\', '', $mapping['collectionClass']) . 'Persistent';
        $className = $this->collectionNamespace . '\\' . $collClassName;
        if ( ! class_exists($className, false)) {
            $fileName = $this->collectionDir . DIRECTORY_SEPARATOR . $collClassName . '.php';
            switch ($this->autoGenerate) {
                case Configuration::AUTOGENERATE_NEVER:
                    require $fileName;
                    break;

                case Configuration::AUTOGENERATE_ALWAYS:
                    $this->generateCollectionClass($mapping['collectionClass'], $className, $fileName);
                    require $fileName;
                    break;

                case Configuration::AUTOGENERATE_FILE_NOT_EXISTS:
                    if ( ! file_exists($fileName)) {
                        $this->generateCollectionClass($mapping['collectionClass'], $className, $fileName);
                    }
                    require $fileName;
                    break;

                case Configuration::AUTOGENERATE_EVAL:
                    $this->generateCollectionClass($mapping['collectionClass'], $className, false);
                    break;
            }
        }
        return new $className($coll, $this->dm, $this->uow);
    }

    /**
     * {@inheritdoc}
     */
    public function generateClass($class, $dir)
    {
        $collClassName = str_replace('\\', '', $class) . 'Persistent';
        $className = $this->collectionNamespace . '\\' . $collClassName;
        $fileName = $dir . DIRECTORY_SEPARATOR . $collClassName . '.php';
        $this->generateCollectionClass($class, $className, $fileName);
    }

    private function generateCollectionClass($for, $targetFqcn, $fileName)
    {
        $exploded = explode('\\', $targetFqcn);
        $class = array_pop($exploded);
        $namespace = join('\\', $exploded);
        $code = <<<CODE
<?php

namespace $namespace;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE\'S PERSISTENT COLLECTION GENERATOR
 */
class $class extends \\$for implements \\Doctrine\\ODM\\MongoDB\\PersistentCollection\\PersistentCollectionInterface
{
    use \\Doctrine\\ODM\\MongoDB\\PersistentCollection\\PersistentCollectionTrait;

    /**
     * @param BaseCollection \$coll
     * @param DocumentManager \$dm
     * @param UnitOfWork \$uow
     */
    public function __construct(BaseCollection \$coll, DocumentManager \$dm, UnitOfWork \$uow)
    {
        \$this->coll = \$coll;
        \$this->dm = \$dm;
        \$this->uow = \$uow;
    }

CODE;
        $rc = new \ReflectionClass($for);
        $rt = new \ReflectionClass('Doctrine\\ODM\\MongoDB\\PersistentCollection\\PersistentCollectionTrait');
        foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $rt->hasMethod($method->getName()) ||
                $method->isConstructor() ||
                $method->isFinal() ||
                $method->isStatic()
            ) {
                continue;
            }
            $code .= $this->generateMethod($method);
        }
        $code .= "}\n";

        if ($fileName === false) {
            if ( ! class_exists($targetFqcn)) {
                eval(substr($code, 5));
            }
        } else {
            $parentDirectory = dirname($fileName);

            if ( ! is_dir($parentDirectory) && (false === @mkdir($parentDirectory, 0775, true))) {
                throw PersistentCollectionException::directoryNotWritable();
            }

            if ( ! is_writable($parentDirectory)) {
                throw PersistentCollectionException::directoryNotWritable();
            }

            $tmpFileName = $fileName . '.' . uniqid('', true);
            file_put_contents($tmpFileName, $code);
            rename($tmpFileName, $fileName);
        }
    }

    private function generateMethod(\ReflectionMethod $method)
    {
        $parametersString = $this->buildParametersString($method, $method->getParameters());
        $callParamsString = implode(', ', $this->getParameterNamesForDecoratedCall($method->getParameters()));

        $method = <<<CODE

    /**
     * {@inheritDoc}
     */
    public function {$method->getName()}($parametersString)
    {
        \$this->initialize();
        return \$this->coll->{$method->getName()}($callParamsString);
    }

CODE;
        return $method;
    }

    /**
     * @param \ReflectionMethod      $method
     * @param \ReflectionParameter[] $parameters
     *
     * @return string
     */
    private function buildParametersString(\ReflectionMethod $method, array $parameters)
    {
        $parameterDefinitions = array();

        /* @var $param \ReflectionParameter */
        foreach ($parameters as $param) {
            $parameterDefinition = '';

            if ($parameterType = $this->getParameterType($param)) {
                $parameterDefinition .= $parameterType . ' ';
            }

            if ($param->isPassedByReference()) {
                $parameterDefinition .= '&';
            }

            if (method_exists($param, 'isVariadic')) {
                if ($param->isVariadic()) {
                    $parameterDefinition .= '...';
                }
            }

            $parameters[]     = '$' . $param->getName();
            $parameterDefinition .= '$' . $param->getName();

            if ($param->isDefaultValueAvailable()) {
                $parameterDefinition .= ' = ' . var_export($param->getDefaultValue(), true);
            }

            $parameterDefinitions[] = $parameterDefinition;
        }

        return implode(', ', $parameterDefinitions);
    }

    /**
     * @param \ReflectionParameter $parameter
     *
     * @return string|null
     */
    private function getParameterType(\ReflectionParameter $parameter)
    {
        // We need to pick the type hint class too
        if ($parameter->isArray()) {
            return 'array';
        }

        if (method_exists($parameter, 'isCallable') && $parameter->isCallable()) {
            return 'callable';
        }

        try {
            $parameterClass = $parameter->getClass();

            if ($parameterClass) {
                return '\\' . $parameterClass->getName();
            }
        } catch (\ReflectionException $previous) {
            // @todo ProxyGenerator throws specialized exceptions
            throw $previous;
        }

        return null;
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return string[]
     */
    private function getParameterNamesForDecoratedCall(array $parameters)
    {
        return array_map(
            function (\ReflectionParameter $parameter) {
                $name = '';

                if (method_exists($parameter, 'isVariadic')) {
                    if ($parameter->isVariadic()) {
                        $name .= '...';
                    }
                }

                $name .= '$' . $parameter->getName();

                return $name;
            },
            $parameters
        );
    }
}
