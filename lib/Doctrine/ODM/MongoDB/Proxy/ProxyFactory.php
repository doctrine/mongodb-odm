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

/**
 * This factory is used to create proxy objects for documents at runtime.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
class ProxyFactory
{
    /**
     * Marker for Proxy class names.
     *
     * @var string
     */
    const MARKER = '__CG__';

    /** The DocumentManager this factory is bound to. */
    private $dm;
    /** Whether to automatically (re)generate proxy classes. */
    private $autoGenerate;
    /** The namespace that contains all proxy classes. */
    private $proxyNamespace;
    /** The directory that contains all proxy classes. */
    private $proxyDir;

    /**
     * Used to match very simple id methods that don't need
     * to be proxied since the identifier is known.
     *
     * @var string
     */
    const PATTERN_MATCH_ID_METHOD = '((public\s)?(function\s{1,}%s\s?\(\)\s{1,})\s{0,}{\s{0,}return\s{0,}\$this->%s;\s{0,}})i';

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
        if ( ! $proxyDir) {
            throw ProxyException::proxyDirectoryRequired();
        }
        if ( ! $proxyNs) {
            throw ProxyException::proxyNamespaceRequired();
        }
        $this->dm = $dm;
        $this->proxyDir = $proxyDir;
        $this->autoGenerate = $autoGenerate;
        $this->proxyNamespace = $proxyNs;
    }

    /**
     * Gets a reference proxy instance for the document of the given type and identified by
     * the given identifier.
     *
     * @param string $className
     * @param mixed $identifier
     * @return object
     */
    public function getProxy($className, $identifier)
    {
        $fqn = self::generateProxyClassName($className, $this->proxyNamespace);

        if ( ! class_exists($fqn, false)) {
            $fileName = $this->getProxyFileName($className);
            if ($this->autoGenerate) {
                $this->generateProxyClass($this->dm->getClassMetadata($className), $fileName, self::$proxyClassTemplate);
            }
            require $fileName;
        }

        if ( ! $this->dm->getMetadataFactory()->hasMetadataFor($fqn)) {
            $this->dm->getMetadataFactory()->setMetadataFor($fqn, $this->dm->getClassMetadata($className));
        }

        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister($className);

        return new $fqn($documentPersister, $identifier);
    }

    /**
     * Generate the Proxy file name
     *
     * @param string $className
     * @param string $proxyDir
     *
     * @return string
     */
    private function getProxyFileName($className, $proxyDir = null)
    {
        $proxyDir = $proxyDir ?: $this->proxyDir;
        $proxyDir = rtrim($proxyDir, DIRECTORY_SEPARATOR);

        return $proxyDir . DIRECTORY_SEPARATOR . '__CG__' . str_replace('\\', '', $className) . '.php';
    }

    /**
     * Generates proxy classes for all given classes.
     *
     * @param array $classes The classes (ClassMetadata instances) for which to generate proxies.
     * @param string $toDir The target directory of the proxy classes. If not specified, the
     *                      directory configured on the Configuration of the DocumentManager used
     *                      by this factory is used.
     */
    public function generateProxyClasses(array $classes, $toDir = null)
    {
        foreach ($classes as $class) {
            /* @var $class ClassMetadata */
            if ($class->isMappedSuperclass) {
                continue;
            }

            $proxyFileName = $this->getProxyFileName($class->name, $toDir);
            $this->generateProxyClass($class, $proxyFileName, self::$proxyClassTemplate);
        }
    }

    /**
     * Generates a proxy class file.
     *
     * @param ClassMetadata $class
     * @param string $fileName The path of the file to write to.
     * @param string $template Code template for proxy class.
     *
     * @throws ProxyException
     */
    private function generateProxyClass($class, $fileName, $template)
    {
        $methods = $this->generateMethods($class);
        $sleepImpl = $this->generateSleep($class);
        $cloneImpl = $class->reflClass->hasMethod('__clone') ? 'parent::__clone();' : ''; // hasMethod() checks case-insensitive

        $placeholders = array(
            '<namespace>',
            '<proxyClassName>',
            '<className>',
            '<methods>',
            '<sleepImpl>',
            '<cloneImpl>'
        );

        $className = ltrim($class->name, '\\');
        $proxyClassName = self::generateProxyClassName($class->name, $this->proxyNamespace);
        $parts = explode('\\', strrev($proxyClassName), 2);
        $proxyClassNamespace = strrev($parts[1]);
        $proxyClassName = strrev($parts[0]);

        $replacements = array(
            $proxyClassNamespace,
            $proxyClassName,
            $className,
            $methods,
            $sleepImpl,
            $cloneImpl
        );

        $code = str_replace($placeholders, $replacements, $template);

        $parentDirectory = dirname($fileName);

        if ( ! is_dir($parentDirectory) && (false === @mkdir($parentDirectory, 0775, true))) {
            throw ProxyException::proxyDirectoryNotWritable();
        }

        if ( ! is_writable($parentDirectory)) {
            throw ProxyException::proxyDirectoryNotWritable();
        }

        $tmpFileName = $fileName . '.' . uniqid('', true);
        file_put_contents($tmpFileName, $code);
        rename($tmpFileName, $fileName);
    }

    /**
     * Generates the methods of a proxy class.
     *
     * @param ClassMetadata $class
     * @return string The code of the generated methods.
     */
    private function generateMethods(ClassMetadata $class)
    {
        $methods = '';

        $methodNames = array();
        foreach ($class->reflClass->getMethods() as $method) {
            /* @var $method \ReflectionMethod */
            if ($method->isConstructor() || in_array(strtolower($method->getName()), array("__sleep", "__clone")) || isset($methodNames[$method->getName()])) {
                continue;
            }
            $methodNames[$method->getName()] = true;

            if ($method->isPublic() && ! $method->isFinal() && ! $method->isStatic()) {
                $methods .= "\n" . '    public function ';
                if ($method->returnsReference()) {
                    $methods .= '&';
                }
                $methods .= $method->getName() . '(';
                $firstParam = true;
                $parameterString = $argumentString = '';

                foreach ($method->getParameters() as $param) {
                    if ($firstParam) {
                        $firstParam = false;
                    } else {
                        $parameterString .= ', ';
                        $argumentString .= ', ';
                    }

                    // We need to pick the type hint class too
                    if (($paramClass = $param->getClass()) !== null) {
                        $parameterString .= '\\' . $paramClass->getName() . ' ';
                    } elseif ($param->isArray()) {
                        $parameterString .= 'array ';
                    }

                    if ($param->isPassedByReference()) {
                        $parameterString .= '&';
                    }

                    $parameterString .= '$' . $param->getName();
                    $argumentString .= '$' . $param->getName();

                    if ($param->isDefaultValueAvailable()) {
                        $parameterString .= ' = ' . var_export($param->getDefaultValue(), true);
                    }
                }

                $methods .= $parameterString . ')';
                $methods .= "\n" . '    {' . "\n";
                if ($this->isShortIdentifierGetter($method, $class)) {
                    $identifier = lcfirst(substr($method->getName(), 3));

                    $methods .= '        if ($this->__isInitialized__ === false) {' . "\n";
                    $methods .= '            return $this->__identifier__;' . "\n";
                    $methods .= '        }' . "\n";
                }
                $methods .= '        $this->__load();' . "\n";
                $methods .= '        return parent::' . $method->getName() . '(' . $argumentString . ');';
                $methods .= "\n" . '    }' . "\n";
            }
        }

        return $methods;
    }

    /**
     * Check if the method is a short identifier getter.
     *
     * What does this mean? For proxy objects the identifier is already known,
     * however accessing the getter for this identifier usually triggers the
     * lazy loading, leading to a query that may not be necessary if only the
     * ID is interesting for the userland code (for example in views that
     * generate links to the document, but do not display anything else).
     *
     * @param \ReflectionMethod $method
     * @param ClassMetadata $class
     * @return bool
     */
    private function isShortIdentifierGetter($method, $class)
    {
        $identifier = lcfirst(substr($method->getName(), 3));
        $cheapCheck = (
            $method->getNumberOfParameters() == 0 &&
            substr($method->getName(), 0, 3) == "get" &&
            $class->identifier === $identifier &&
            $class->hasField($identifier) &&
            (($method->getEndLine() - $method->getStartLine()) <= 4)
            && in_array($class->fieldMappings[$identifier]['type'], array('id', 'int_id', 'custom_id'))
        );

        if ($cheapCheck) {
            $code = file($method->getDeclaringClass()->getFileName());
            $code = trim(implode(" ", array_slice($code, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1)));

            $pattern = sprintf(self::PATTERN_MATCH_ID_METHOD, $method->getName(), $identifier);

            if (preg_match($pattern, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generates the code for the __sleep method for a proxy class.
     *
     * @param ClassMetadata $class
     * @return string
     */
    private function generateSleep(ClassMetadata $class)
    {
        $sleepImpl = '';

        if ($class->reflClass->hasMethod('__sleep')) {
            $sleepImpl .= "return array_merge(array('__isInitialized__'), parent::__sleep());";
        } else {
            $sleepImpl .= "return array('__isInitialized__', ";
            $first = true;

            foreach ($class->getReflectionProperties() as $name => $prop) {
                if ($first) {
                    $first = false;
                } else {
                    $sleepImpl .= ', ';
                }

                $sleepImpl .= "'" . $name . "'";
            }

            $sleepImpl .= ');';
        }

        return $sleepImpl;
    }

    /** Proxy class code template */
    private static $proxyClassTemplate =
'<?php

namespace <namespace>;

use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ODM. DO NOT EDIT THIS FILE.
 */
class <proxyClassName> extends \<className> implements \Doctrine\ODM\MongoDB\Proxy\Proxy
{
    private $__documentPersister__;
    public $__identifier__;
    public $__isInitialized__ = false;
    public function __construct(DocumentPersister $documentPersister, $identifier)
    {
        $this->__documentPersister__ = $documentPersister;
        $this->__identifier__ = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->__documentPersister__) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->__documentPersister__->load($this->__identifier__, $this) === null) {
                throw \Doctrine\ODM\MongoDB\DocumentNotFoundException::documentNotFound(get_class($this), $this->__identifier__);
            }
            unset($this->__documentPersister__, $this->__identifier__);
        }
    }

    /** @private */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    <methods>

    public function __sleep()
    {
        <sleepImpl>
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->__documentPersister__) {
            $this->__isInitialized__ = true;
            $class = $this->__documentPersister__->getClassMetadata();
            $original = $this->__documentPersister__->load($this->__identifier__);
            if ($original === null) {
                throw \Doctrine\ODM\MongoDB\MongoDBException::documentNotFound(get_class($this), $this->__identifier__);
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->__documentPersister__, $this->__identifier__);
        }
        <cloneImpl>
    }
}';

    public static function generateProxyClassName($className, $proxyNamespace)
    {
        return rtrim($proxyNamespace, '\\') . '\\' . self::MARKER . '\\' . ltrim($className, '\\');
    }
}
