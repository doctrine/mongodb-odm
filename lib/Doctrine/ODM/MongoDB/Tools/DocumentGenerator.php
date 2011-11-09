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

namespace Doctrine\ODM\MongoDB\Tools;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo,
    Doctrine\Common\Util\Inflector;

/**
 * Generic class used to generate PHP5 document classes from ClassMetadataInfo instances
 *
 *     [php]
 *     $classes = $dm->getClassMetadataInfoFactory()->getAllMetadata();
 *
 *     $generator = new \Doctrine\ODM\MongoDB\Tools\DocumentGenerator();
 *     $generator->setGenerateAnnotations(true);
 *     $generator->setGenerateStubMethods(true);
 *     $generator->setRegenerateDocumentIfExists(false);
 *     $generator->setUpdateDocumentIfExists(true);
 *     $generator->generate($classes, '/path/to/generate/documents');
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class DocumentGenerator
{
    /**
     * @var bool
     */
    private $backupExisting = true;

    /** The extension to use for written php files */
    private $extension = '.php';

    /** Whether or not the current ClassMetadataInfo instance is new or old */
    private $isNew = true;

    private $staticReflection = array();

    /** Number of spaces to use for indention in generated code */
    private $numSpaces = 4;

    /** The actual spaces to use for indention */
    private $spaces = '    ';

    /** The class all generated documents should extend */
    private $classToExtend;

    /** Whether or not to generation annotations */
    private $generateAnnotations = false;

    /** Whether or not to generated sub methods */
    private $generateDocumentStubMethods = false;

    /** Whether or not to update the document class if it exists already */
    private $updateDocumentIfExists = false;

    /** Whether or not to re-generate document class if it exists already */
    private $regenerateDocumentIfExists = false;

    private static $classTemplate =
'<?php

<namespace>

<imports>

<documentAnnotation>
<documentClassName>
{
<documentBody>
}';

    private static $getMethodTemplate =
'/**
 * <description>
 *
 * @return <variableType>$<variableName>
 */
public function <methodName>()
{
<spaces>return $this-><fieldName>;
}';

    private static $setMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName> = $<variableName>;
}';

    private static $addMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName>[] = $<variableName>;
}';

    private static $lifecycleCallbackMethodTemplate =
'<comment>
public function <methodName>()
{
<spaces>// Add your code here
}';

    private static $constructorMethodTemplate =
'public function __construct()
{
<collections>
}
';

    /**
     * Generate and write document classes for the given array of ClassMetadataInfo instances
     *
     * @param array $metadatas
     * @param string $outputDirectory 
     * @return void
     */
    public function generate(array $metadatas, $outputDirectory)
    {
        foreach ($metadatas as $metadata) {
            $this->writeDocumentClass($metadata, $outputDirectory);
        }
    }

    /**
     * Generated and write document class to disk for the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata
     * @param string $outputDirectory 
     * @return void
     */
    public function writeDocumentClass(ClassMetadataInfo $metadata, $outputDirectory)
    {
        $path = $outputDirectory . '/' . str_replace('\\', DIRECTORY_SEPARATOR, $metadata->name) . $this->extension;
        $dir = dirname($path);

        if ( ! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->isNew = !file_exists($path) || (file_exists($path) && $this->regenerateDocumentIfExists);

        if ( ! $this->isNew) {
            $this->parseTokensInDocumentFile($path);
        }

        if ($this->backupExisting && file_exists($path)) {
            $backupPath = dirname($path) . DIRECTORY_SEPARATOR . basename($path) . '~' ;
            if (!copy($path, $backupPath)) {
                throw new \RuntimeException("Attempt to backup overwritten document file but copy operation failed.");
            }
        }

        // If document doesn't exist or we're re-generating the documents entirely
        if ($this->isNew) {
            file_put_contents($path, $this->generateDocumentClass($metadata));
        // If document exists and we're allowed to update the document class
        } else if ( ! $this->isNew && $this->updateDocumentIfExists) {
            file_put_contents($path, $this->generateUpdatedDocumentClass($metadata, $path));
        }
    }

    /**
     * Generate a PHP5 Doctrine 2 document class from the given ClassMetadataInfo instance
     *
     * @param ClassMetadataInfo $metadata 
     * @return string $code
     */
    public function generateDocumentClass(ClassMetadataInfo $metadata)
    {
        $placeHolders = array(
            '<namespace>',
            '<imports>',
            '<documentAnnotation>',
            '<documentClassName>',
            '<documentBody>'
        );

        $replacements = array(
            $this->generateDocumentNamespace($metadata),
            $this->generateDocumentImports($metadata),
            $this->generateDocumentDocBlock($metadata),
            $this->generateDocumentClassName($metadata),
            $this->generateDocumentBody($metadata)
        );

        $code = str_replace($placeHolders, $replacements, self::$classTemplate);
        return str_replace('<spaces>', $this->spaces, $code);
    }

    /**
     * Generate the updated code for the given ClassMetadataInfo and document at path
     *
     * @param ClassMetadataInfo $metadata 
     * @param string $path 
     * @return string $code;
     */
    public function generateUpdatedDocumentClass(ClassMetadataInfo $metadata, $path)
    {
        $currentCode = file_get_contents($path);

        $body = $this->generateDocumentBody($metadata);
        $body = str_replace('<spaces>', $this->spaces, $body);
        $last = strrpos($currentCode, '}');

        return substr($currentCode, 0, $last) . $body . (strlen($body) > 0 ? "\n" : ''). "}\n";
    }

    /**
     * Set the number of spaces the exported class should have
     *
     * @param integer $numSpaces 
     * @return void
     */
    public function setNumSpaces($numSpaces)
    {
        $this->spaces = str_repeat(' ', $numSpaces);
        $this->numSpaces = $numSpaces;
    }

    /**
     * Set the extension to use when writing php files to disk
     *
     * @param string $extension 
     * @return void
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Set the name of the class the generated classes should extend from
     *
     * @return void
     */
    public function setClassToExtend($classToExtend)
    {
        $this->classToExtend = $classToExtend;
    }

    /**
     * Set whether or not to generate annotations for the document
     *
     * @param bool $bool 
     * @return void
     */
    public function setGenerateAnnotations($bool)
    {
        $this->generateAnnotations = $bool;
    }

    /**
     * Set whether or not to try and update the document if it already exists
     *
     * @param bool $bool 
     * @return void
     */
    public function setUpdateDocumentIfExists($bool)
    {
        $this->updateDocumentIfExists = $bool;
    }

    /**
     * Set whether or not to regenerate the document if it exists
     *
     * @param bool $bool
     * @return void
     */
    public function setRegenerateDocumentIfExists($bool)
    {
        $this->regenerateDocumentIfExists = $bool;
    }

    /**
     * Set whether or not to generate stub methods for the document
     *
     * @param bool $bool
     * @return void
     */
    public function setGenerateStubMethods($bool)
    {
        $this->generateDocumentStubMethods = $bool;
    }

    /**
     * Should an existing document be backed up if it already exists?
     */
    public function setBackupExisting($bool)
    {
        $this->backupExisting = $bool;
    }

    private function generateDocumentNamespace(ClassMetadataInfo $metadata)
    {
        if ($this->hasNamespace($metadata)) {
            return 'namespace ' . $this->getNamespace($metadata) .';';
        }
    }

    private function generateDocumentClassName(ClassMetadataInfo $metadata)
    {
        return 'class ' . $this->getClassName($metadata) .
            ($this->extendsClass() ? ' extends ' . $this->getClassToExtendName() : null);
    }

    private function generateDocumentBody(ClassMetadataInfo $metadata)
    {
        $fieldMappingProperties = $this->generateDocumentFieldMappingProperties($metadata);
        $associationMappingProperties = $this->generateDocumentAssociationMappingProperties($metadata);
        $stubMethods = $this->generateDocumentStubMethods ? $this->generateDocumentStubMethods($metadata) : null;
        $lifecycleCallbackMethods = $this->generateDocumentLifecycleCallbackMethods($metadata);

        $code = array();

        if ($fieldMappingProperties) {
            $code[] = $fieldMappingProperties;
        }

        if ($associationMappingProperties) {
            $code[] = $associationMappingProperties;
        }

        $code[] = $this->generateDocumentConstructor($metadata);

        if ($stubMethods) {
            $code[] = $stubMethods;
        }

        if ($lifecycleCallbackMethods) {
            $code[] = "\n".$lifecycleCallbackMethods;
        }

        return implode("\n", $code);
    }

    private function generateDocumentConstructor(ClassMetadataInfo $metadata)
    {
        if ($this->hasMethod('__construct', $metadata)) {
            return '';
        }

        $collections = array();
        foreach ($metadata->fieldMappings AS $mapping) {
            if ($mapping['type'] === ClassMetadataInfo::MANY) {
                $collections[] = '$this->'.$mapping['fieldName'].' = new \Doctrine\Common\Collections\ArrayCollection();';
            }
        }
        if ($collections) {
            return $this->prefixCodeWithSpaces(str_replace("<collections>", $this->spaces.implode("\n".$this->spaces, $collections), self::$constructorMethodTemplate));
        }
        return '';
    }

    /**
     * @todo this won't work if there is a namespace in brackets and a class outside of it.
     * @param string $path
     */
    private function parseTokensInDocumentFile($path)
    {
        $tokens = token_get_all(file_get_contents($path));
        $lastSeenNamespace = '';
        $lastSeenClass = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if ($token[0] == T_NAMESPACE) {
                $peek = $i;
                $lastSeenNamespace = '';
                while (isset($tokens[++$peek])) {
                    if (';' == $tokens[$peek]) {
                        break;
                    } elseif (is_array($tokens[$peek]) && in_array($tokens[$peek][0], array(T_STRING, T_NS_SEPARATOR))) {
                        $lastSeenNamespace .= $tokens[$peek][1];
                    }
                }
            } else if ($token[0] == T_CLASS) {
                $lastSeenClass = $lastSeenNamespace . '\\' . $tokens[$i+2][1];
                $this->staticReflection[$lastSeenClass]['properties'] = array();
                $this->staticReflection[$lastSeenClass]['methods'] = array();
            } else if ($token[0] == T_FUNCTION) {
                if ($tokens[$i+2][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = $tokens[$i+2][1];
                } else if ($tokens[$i+2][0] == '&' && $tokens[$i+3][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = $tokens[$i+3][1];
                }
            } else if (in_array($token[0], array(T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED)) && $tokens[$i+2][0] != T_FUNCTION) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i+2][1], 1);
            }
        }
    }

    private function hasProperty($property, ClassMetadataInfo $metadata)
    {
        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($property, $this->staticReflection[$metadata->name]['properties'])
        );
    }

    private function hasMethod($method, ClassMetadataInfo $metadata)
    {
        return (
            isset($this->staticReflection[$metadata->name]) &&
            in_array($method, $this->staticReflection[$metadata->name]['methods'])
        );
    }

    private function hasNamespace(ClassMetadataInfo $metadata)
    {
        return strpos($metadata->name, '\\') ? true : false;
    }

    private function extendsClass()
    {
        return $this->classToExtend ? true : false;
    }

    private function getClassToExtend()
    {
        return $this->classToExtend;
    }

    private function getClassToExtendName()
    {
        $refl = new \ReflectionClass($this->getClassToExtend());

        return '\\' . $refl->getName();
    }

    private function getClassName(ClassMetadataInfo $metadata)
    {
        return ($pos = strrpos($metadata->name, '\\'))
            ? substr($metadata->name, $pos + 1, strlen($metadata->name)) : $metadata->name;
    }

    private function getNamespace(ClassMetadataInfo $metadata)
    {
        return substr($metadata->name, 0, strrpos($metadata->name, '\\'));
    }

    private function generateDocumentImports(ClassMetadataInfo $metadata)
    {
        if ($this->generateAnnotations) {
            return 'use Doctrine\\ODM\\MongoDB\\Mapping\\Annotations as ODM;';
        }
    }

    private function generateDocumentDocBlock(ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = '/**';
        $lines[] = ' * '.$metadata->name;

        if ($this->generateAnnotations) {
            $lines[] = ' *';

            if ($metadata->isMappedSuperclass) {
                $lines[] = ' * @ODM\\MappedSupperClass';
            } else if ($metadata->isEmbeddedDocument) {
                $lines[] = ' * @ODM\\EmbeddedDocument';
            } else {
                $lines[] = ' * @ODM\\Document';
            }

            $document = array();
            if (! $metadata->isMappedSuperclass && ! $metadata->isEmbeddedDocument) {
                if ($metadata->collection) {
                    $document[] = ' *     collection="' . $metadata->collection . '"';
                }
                if ($metadata->customRepositoryClassName) {
                    $document[] = ' *     repositoryClass="' . $metadata->customRepositoryClassName . '"';
                }
            }
            if ($metadata->indexes) {
                $indexes = array();
                $indexLines = array();
                $indexLines[] = " *     indexes={";
                foreach ($metadata->indexes as $index) {
                    $keys = array();
                    foreach ($index['keys'] as $key => $value) {
                        $keys[] = '"'.$key.'"="'.$value.'"';
                    }
                    $options = array();
                    foreach ($index['options'] as $key => $value) {
                        $options[] = '"'.$key.'"="'.$value.'"';
                    }
                    $indexes[] = '@ODM\\Index(keys={' . implode(', ', $keys) . '}, options={' . implode(', ', $options) . '})';
                }
                $indexLines[] = "\n *         " . implode(",\n *         ", $indexes);
                $indexLines[] = "\n *     }";

                $document[] = implode(null, $indexLines);
            }

            if ($document) {
                $lines[count($lines) - 1] .= '(';
                $lines[] = implode(",\n", $document);
                $lines[] = ' * )';
            }

            $methods = array(
                'generateInheritanceAnnotation',
                'generateDiscriminatorFieldAnnotation',
                'generateDiscriminatorMapAnnotation',
                'generateChangeTrackingPolicyAnnotation'
            );

            foreach ($methods as $method) {
                if ($code = $this->$method($metadata)) {
                    $lines[] = ' * ' . $code;
                }
            }
        }

        $lines[] = ' */';
        return implode("\n", $lines);
    }

    private function generateInheritanceAnnotation($metadata)
    {
        if ($metadata->inheritanceType != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            return '@ODM\\InheritanceType("'.$this->getInheritanceTypeString($metadata->inheritanceType).'")';
        }
    }

    private function generateDiscriminatorFieldAnnotation($metadata)
    {
        if ($metadata->inheritanceType != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            $discrField = $metadata->discriminatorField;
            return '@ODM\\DiscriminatorField(fieldName="' . $discrField['fieldName'] . '")';
        }
    }

    private function generateDiscriminatorMapAnnotation(ClassMetadataInfo $metadata)
    {
        if ($metadata->inheritanceType != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
            $inheritanceClassMap = array();

            foreach ($metadata->discriminatorMap as $type => $class) {
                $inheritanceClassMap[] .= '"' . $type . '" = "' . $class . '"';
            }

            return '@ODM\\DiscriminatorMap({' . implode(', ', $inheritanceClassMap) . '})';
        }
    }

    private function generateChangeTrackingPolicyAnnotation(ClassMetadataInfo $metadata)
    {
        return '@ODM\\ChangeTrackingPolicy("' . $this->getChangeTrackingPolicyString($metadata->changeTrackingPolicy) . '")';
    }

    private function generateDocumentStubMethods(ClassMetadataInfo $metadata)
    {
        $methods = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['id'])) {
                if ($metadata->generatorType == ClassMetadataInfo::GENERATOR_TYPE_NONE) {
                    if ($code = $this->generateDocumentStubMethod($metadata, 'set', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                        $methods[] = $code;
                    }
                }
                if ($code = $code = $this->generateDocumentStubMethod($metadata, 'get', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                    $methods[] = $code;
                }
            } else if ( ! isset($fieldMapping['association'])) {
                if ($code = $code = $this->generateDocumentStubMethod($metadata, 'set', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                    $methods[] = $code;
                }
                if ($code = $code = $this->generateDocumentStubMethod($metadata, 'get', $fieldMapping['fieldName'], $fieldMapping['type'])) {
                    $methods[] = $code;
                }
            } else if ($fieldMapping['type'] === ClassMetadataInfo::ONE) {
                if ($code = $this->generateDocumentStubMethod($metadata, 'set', $fieldMapping['fieldName'], isset($fieldMapping['targetDocument']) ? $fieldMapping['targetDocument'] : null)) {
                    $methods[] = $code;
                }
                if ($code = $this->generateDocumentStubMethod($metadata, 'get', $fieldMapping['fieldName'], isset($fieldMapping['targetDocument']) ? $fieldMapping['targetDocument'] : null)) {
                    $methods[] = $code;
                }
            } else if ($fieldMapping['type'] === ClassMetadataInfo::MANY) {
                if ($code = $this->generateDocumentStubMethod($metadata, 'add', $fieldMapping['fieldName'], isset($fieldMapping['targetDocument']) ? $fieldMapping['targetDocument'] : null)) {
                    $methods[] = $code;
                }
                if ($code = $this->generateDocumentStubMethod($metadata, 'get', $fieldMapping['fieldName'], 'Doctrine\Common\Collections\Collection')) {
                    $methods[] = $code;
                }
            }
        }

        return implode("\n\n", $methods);
    }

    private function generateDocumentLifecycleCallbackMethods(ClassMetadataInfo $metadata)
    {
        if (isset($metadata->lifecycleCallbacks) && $metadata->lifecycleCallbacks) {
            $methods = array();

            foreach ($metadata->lifecycleCallbacks as $name => $callbacks) {
                foreach ($callbacks as $callback) {
                    if ($code = $this->generateLifecycleCallbackMethod($name, $callback, $metadata)) {
                        $methods[] = $code;
                    }
                }
            }

            return implode("\n\n", $methods);
        }

        return "";
    }

    private function generateDocumentAssociationMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if ($this->hasProperty($fieldMapping['fieldName'], $metadata) ||
                $metadata->isInheritedField($fieldMapping['fieldName'])) {
                continue;
            }
            if ( ! isset($fieldMapping['association'])) {
                continue;
            }
    
            $lines[] = $this->generateAssociationMappingPropertyDocBlock($fieldMapping, $metadata);
            $lines[] = $this->spaces . 'protected $' . $fieldMapping['fieldName']
                     . ($fieldMapping['type'] === ClassMetadataInfo::MANY ? ' = array()' : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    private function generateDocumentFieldMappingProperties(ClassMetadataInfo $metadata)
    {
        $lines = array();

        foreach ($metadata->fieldMappings as $fieldMapping) {
            if ($this->hasProperty($fieldMapping['fieldName'], $metadata) ||
                $metadata->isInheritedField($fieldMapping['fieldName'])) {
                continue;
            }
            if (isset($fieldMapping['association']) && $fieldMapping['association']) {
                continue;
            }

            $lines[] = $this->generateFieldMappingPropertyDocBlock($fieldMapping, $metadata);
            $lines[] = $this->spaces . 'protected $' . $fieldMapping['fieldName']
                     . (isset($fieldMapping['default']) ? ' = ' . var_export($fieldMapping['default'], true) : null) . ";\n";
        }

        return implode("\n", $lines);
    }

    private function generateDocumentStubMethod(ClassMetadataInfo $metadata, $type, $fieldName, $typeHint = null)
    {
        $methodName = $type . Inflector::classify($fieldName);

        if ($this->hasMethod($methodName, $metadata)) {
            return;
        }

        $var = sprintf('%sMethodTemplate', $type);
        $template = self::$$var;

        $variableType = $typeHint ? $typeHint . ' ' : null;

        $types = \Doctrine\ODM\MongoDB\Mapping\Types\Type::getTypesMap();
        $methodTypeHint = $typeHint && ! isset($types[$typeHint]) ? '\\' . $typeHint . ' ' : null;

        $replacements = array(
          '<description>'       => ucfirst($type) . ' ' . $fieldName,
          '<methodTypeHint>'    => $methodTypeHint,
          '<variableType>'      => $variableType,
          '<variableName>'      => Inflector::camelize($fieldName),
          '<methodName>'        => $methodName,
          '<fieldName>'         => $fieldName
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        return $this->prefixCodeWithSpaces($method);
    }

    private function generateLifecycleCallbackMethod($name, $methodName, $metadata)
    {
        if ($this->hasMethod($methodName, $metadata)) {
            return;
        }

        $replacements = array(
            '<comment>'    => $this->generateAnnotations ? '/** @ODM\\'.ucfirst($name).' */' : '',
            '<methodName>' => $methodName,
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            self::$lifecycleCallbackMethodTemplate
        );

        return $this->prefixCodeWithSpaces($method);
    }

    private function generateAssociationMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var ' . (isset($fieldMapping['targetDocument']) ? $fieldMapping['targetDocument'] : 'object');

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $type = null;
            switch ($fieldMapping['association']) {
                case ClassMetadataInfo::EMBED_ONE:
                    $type = 'EmbedOne';
                    break;
                case ClassMetadataInfo::EMBED_MANY:
                    $type = 'EmbedMany';
                    break;
                case ClassMetadataInfo::REFERENCE_ONE:
                    $type = 'ReferenceOne';
                    break;
                case ClassMetadataInfo::REFERENCE_MANY:
                    $type = 'ReferenceMany';
                    break;
            }
            $typeOptions = array();

            if (isset($fieldMapping['targetDocument'])) {
                $typeOptions[] = 'targetDocument="' . $fieldMapping['targetDocument'] . '"';
            }

            if (isset($fieldMapping['cascade']) && $fieldMapping['cascade']) {
                $cascades = array();

                if ($fieldMapping['isCascadePersist']) $cascades[] = '"persist"';
                if ($fieldMapping['isCascadeRemove']) $cascades[] = '"remove"';
                if ($fieldMapping['isCascadeDetach']) $cascades[] = '"detach"';
                if ($fieldMapping['isCascadeMerge']) $cascades[] = '"merge"';
                if ($fieldMapping['isCascadeRefresh']) $cascades[] = '"refresh"';

                $typeOptions[] = 'cascade={' . implode(',', $cascades) . '}';            
            }

            $lines[] = $this->spaces . ' * @ODM\\' . $type . '(' . implode(', ', $typeOptions) . ')';
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    private function generateFieldMappingPropertyDocBlock(array $fieldMapping, ClassMetadataInfo $metadata)
    {
        $lines = array();
        $lines[] = $this->spaces . '/**';
        if (isset($fieldMapping['id']) && $fieldMapping['id']) {
            $fieldMapping['strategy'] = isset($fieldMapping['strategy']) ? $fieldMapping['strategy'] : ClassMetadataInfo::GENERATOR_TYPE_AUTO;
            if ($fieldMapping['strategy'] === ClassMetadataInfo::GENERATOR_TYPE_AUTO) {
                $lines[] = $this->spaces . ' * @var MongoId $' . $fieldMapping['fieldName'];
            } elseif ($fieldMapping['strategy'] === ClassMetadataInfo::GENERATOR_TYPE_INCREMENT) {
                $lines[] = $this->spaces . ' * @var integer $' . $fieldMapping['fieldName'];
            } elseif ($fieldMapping['strategy'] === ClassMetadataInfo::GENERATOR_TYPE_UUID) {
                $lines[] = $this->spaces . ' * @var string $' . $fieldMapping['fieldName'];
            } elseif ($fieldMapping['strategy'] === ClassMetadataInfo::GENERATOR_TYPE_NONE) {
                $lines[] = $this->spaces . ' * @var $' . $fieldMapping['fieldName'];
            } else {
                $lines[] = $this->spaces . ' * @var $' . $fieldMapping['fieldName'];
            }
        } else {
            $lines[] = $this->spaces . ' * @var ' . $fieldMapping['type'] . ' $' . $fieldMapping['fieldName'];
        }

        if ($this->generateAnnotations) {
            $lines[] = $this->spaces . ' *';

            $field = array();
            if (isset($fieldMapping['id']) && $fieldMapping['id']) {
                if (isset($fieldMapping['strategy'])) {
                    $field[] = 'strategy="' . $this->getIdGeneratorTypeString($metadata->generatorType) . '"';
                }
                $lines[] = $this->spaces . ' * @ODM\\Id(' . implode(', ', $field) . ')';
            } else {
                if (isset($fieldMapping['name'])) {
                    $field[] = 'name="' . $fieldMapping['name'] . '"';
                }

                if (isset($fieldMapping['type'])) {
                    $field[] = 'type="' . $fieldMapping['type'] . '"';
                }

                if (isset($fieldMapping['nullable']) && $fieldMapping['nullable'] === true) {
                    $field[] = 'nullable=' .  var_export($fieldMapping['nullable'], true);
                }
                if (isset($fieldMapping['options'])) {
                    $options = array();
                    foreach ($fieldMapping['options'] as $key => $value) {
                        $options[] = '"' . $key . '" = "' . $value . '"';
                    }
                    $field[] = "options={".implode(', ', $options)."}";
                }
                $lines[] = $this->spaces . ' * @ODM\\Field(' . implode(', ', $field) . ')';
            }

            if (isset($fieldMapping['version']) && $fieldMapping['version']) {
                $lines[] = $this->spaces . ' * @ODM\\Version';
            }
        }

        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    private function prefixCodeWithSpaces($code, $num = 1)
    {
        $lines = explode("\n", $code);

        foreach ($lines as $key => $value) {
            $lines[$key] = str_repeat($this->spaces, $num) . $lines[$key];
        }

        return implode("\n", $lines);
    }

    private function getInheritanceTypeString($type)
    {
        switch ($type) {
            case ClassMetadataInfo::INHERITANCE_TYPE_NONE:
                return 'NONE';

            case ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_COLLECTION:
                return 'SINGLE_COLLECTION';

            case ClassMetadataInfo::INHERITANCE_TYPE_COLLECTION_PER_CLASS:
                return 'COLLECTION_PER_CLASS';

            default:
                throw new \InvalidArgumentException('Invalid provided InheritanceType: ' . $type);
        }
    }

    private function getChangeTrackingPolicyString($policy)
    {
        switch ($policy) {
            case ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT:
                return 'DEFERRED_IMPLICIT';

            case ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT:
                return 'DEFERRED_EXPLICIT';

            case ClassMetadataInfo::CHANGETRACKING_NOTIFY:
                return 'NOTIFY';

            default:
                throw new \InvalidArgumentException('Invalid provided ChangeTrackingPolicy: ' . $policy);
        }
    }

    private function getIdGeneratorTypeString($type)
    {
        switch ($type) {
            case ClassMetadataInfo::GENERATOR_TYPE_AUTO:
                return 'AUTO';

            case ClassMetadataInfo::GENERATOR_TYPE_INCREMENT:
                return 'INCREMENT';

            case ClassMetadataInfo::GENERATOR_TYPE_UUID:
                return 'UUID';

            case ClassMetadataInfo::GENERATOR_TYPE_NONE:
                return 'NONE';

            default:
                throw new \InvalidArgumentException('Invalid provided IdGeneratorType: ' . $type);
        }
    }
}
