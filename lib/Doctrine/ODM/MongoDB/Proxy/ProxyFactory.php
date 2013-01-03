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

namespace Doctrine\ODM\MongoDB\Proxy;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Proxy\Proxy;

/**
 * This factory is used to create proxy objects for documents at runtime.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @author      Marco Pivetta <ocramius@gmail.com>
 */
class ProxyFactory
{
    /**
     * @var DocumentManager The DocumentManager this factory is bound to.
     */
    private $dm;

    /**
     * @var \Doctrine\ODM\MongoDB\UnitOfWork The UnitOfWork this factory is bound to.
     */
    private $uow;

    /**
     * @var bool Whether to automatically (re)generate proxy classes.
     */
    private $autoGenerate;

    /**
     * @var string The namespace that contains all proxy classes.
     */
    private $proxyNamespace;

    /**
     * @var string The directory that contains all proxy classes.
     */
    private $proxyDir;

    /**
     * @var ProxyGenerator the proxy generator responsible for creating the proxy classes/files.
     */
    private $proxyGenerator;

    /**
     * @var array definitions (indexed by requested class name) for the proxy classes.
     *            Each element is an array containing following items:
     *            "fqcn" - FQCN of the proxy class
     *            "initializer" - Closure to be used as proxy __initializer__
     *            "cloner" - Closure to be used as proxy __cloner__
     *            "reflectionId" - ReflectionProperty for the ID field
     */
    private $definitions = array();

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>DocumentManager</tt>.
     *
     * @param DocumentManager $dm The DocumentManager the new factory works for.
     * @param string $proxyDir The directory to use for the proxy classes. It must exist.
     * @param string $proxyNs The namespace to use for the proxy classes.
     * @param boolean $autoGenerate Whether to automatically generate proxy classes.
     */
    public function __construct(DocumentManager $dm, $proxyDir, $proxyNs, $autoGenerate = false)
    {
        $this->dm = $dm;
        $this->uow = $dm->getUnitOfWork();
        $this->proxyDir = $proxyDir;
        $this->autoGenerate = $autoGenerate;
        $this->proxyNamespace = $proxyNs;
    }

    /**
     * Gets a reference proxy instance for the proxy of the given type and identified by
     * the given identifier.
     *
     * @param  string $className
     * @param  mixed $identifier
     * @return object
     */
    public function getProxy($className, $identifier)
    {
        if ( ! isset($this->definitions[$className])) {
            $this->initProxyDefinitions($className);
        }

        $definition   = $this->definitions[$className];
        $fqcn         = $definition['fqcn'];
        $reflectionId = $definition['reflectionId'];
        $proxy        = new $fqcn($definition['initializer'], $definition['cloner']);
        $reflectionId->setValue($proxy, $identifier);

        return $proxy;
    }

    /**
     * Generates proxy classes for all given classes.
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata[] $classes The classes (ClassMetadata instances)
     *                                                                      for which to generate proxies.
     * @param string $proxyDir The target directory of the proxy classes. If not specified, the
     *                         directory configured on the Configuration of the DocumentManager used
     *                         by this factory is used.
     * @return int Number of generated proxies.
     */
    public function generateProxyClasses(array $classes, $proxyDir = null)
    {
        $generated = 0;

        foreach ($classes as $class) {
            /* @var $class \Doctrine\ODM\Mongodb\Mapping\ClassMetadataInfo */
            if ($class->isMappedSuperclass || $class->getReflectionClass()->isAbstract()) {
                continue;
            }

            $generator = $this->getProxyGenerator();

            $proxyFileName = $generator->getProxyFileName($class->getName(), $proxyDir);
            $generator->generateProxyClass($class, $proxyFileName);
            $generated += 1;
        }

        return $generated;
    }

    /**
     * @param ProxyGenerator $proxyGenerator
     */
    public function setProxyGenerator(ProxyGenerator $proxyGenerator)
    {
        $this->proxyGenerator = $proxyGenerator;
    }

    /**
     * @return ProxyGenerator
     */
    public function getProxyGenerator()
    {
        if (null === $this->proxyGenerator) {
            $this->proxyGenerator = new ProxyGenerator($this->proxyDir, $this->proxyNamespace);
            $this->proxyGenerator->setPlaceholder('<baseProxyInterface>', 'Doctrine\ODM\MongoDB\Proxy\Proxy');
        }

        return $this->proxyGenerator;
    }

    /**
     * @param string $className
     */
    private function initProxyDefinitions($className)
    {
        $fqcn = ClassUtils::generateProxyClassName($className, $this->proxyNamespace);
        $classMetadata = $this->dm->getClassMetadata($className);

        if ( ! class_exists($fqcn, false)) {
            $generator = $this->getProxyGenerator();
            $fileName = $generator->getProxyFileName($className);

            if ($this->autoGenerate) {
                $generator->generateProxyClass($classMetadata);
            }

            require $fileName;
        }

        $documentPersister = $this->uow->getDocumentPersister($className);
        /* @var $reflectionId \ReflectionProperty */
        $reflectionId = $classMetadata->reflFields[$classMetadata->identifier];

        if ($classMetadata->getReflectionClass()->hasMethod('__wakeup')) {
            $initializer = function (Proxy $proxy) use ($documentPersister, $reflectionId) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }

                $proxy->__setInitialized(true);
                $proxy->__wakeup();
                $id = $reflectionId->getValue($proxy);

                if (null === $documentPersister->load($id, $proxy)) {
                    throw DocumentNotFoundException::documentNotFound(get_class($proxy), $id);
                }
            };
        } else {
            $initializer = function (Proxy $proxy) use ($documentPersister, $reflectionId) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                if ($proxy->__isInitialized()) {
                    return;
                }

                $properties = $proxy->__getLazyProperties();

                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }

                $proxy->__setInitialized(true);
                $id = $reflectionId->getValue($proxy);

                if (null === $documentPersister->load($id, $proxy)) {
                    throw DocumentNotFoundException::documentNotFound(get_class($proxy), $id);
                }
            };
        }

        $cloner = function (Proxy $proxy) use ($documentPersister, $classMetadata, $reflectionId) {
            if ($proxy->__isInitialized()) {
                return;
            }

            $proxy->__setInitialized(true);
            $proxy->__setInitializer(null);
            $id = $reflectionId->getValue($proxy);
            $original = $documentPersister->load($id);

            if (null === $original) {
                throw DocumentNotFoundException::documentNotFound(get_class($proxy), $id);
            }

            foreach ($classMetadata->getReflectionClass()->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                if ($classMetadata->hasField($propertyName) || $classMetadata->hasAssociation($propertyName)) {
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($proxy, $reflectionProperty->getValue($original));
                }
            }
        };

        $this->definitions[$className] = array(
            'fqcn'                        => $fqcn,
            'initializer'                 => $initializer,
            'cloner'                      => $cloner,
            'reflectionId'                => $reflectionId,
        );
    }
}
