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

namespace Doctrine\ODM\MongoDB\Mapping;

use Doctrine\Common\Persistence\Mapping\MappingException as BaseMappingException;

/**
 * Class for all exceptions related to the Doctrine MongoDB ODM
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MappingException extends BaseMappingException
{
    public static function typeExists($name)
    {
        return new self('Type '.$name.' already exists.');
    }

    public static function typeNotFound($name)
    {
        return new self('Type to be overwritten '.$name.' does not exist.');
    }

    public static function mappingNotFound($className, $fieldName)
    {
        return new self("No mapping found for field '$fieldName' in class '$className'.");
    }

    public static function duplicateFieldMapping($document, $fieldName)
    {
        return new self('Property "'.$fieldName.'" in "'.$document.'" was already declared, but it must be declared only once');
    }

    /**
     * Throws an exception that indicates that a class used in a discriminator map does not exist.
     * An example would be an outdated (maybe renamed) classname.
     *
     * @param string $className The class that could not be found
     * @param string $owningClass The class that declares the discriminator map.
     * @return self
     */
    public static function invalidClassInDiscriminatorMap($className, $owningClass)
    {
        return new self(
            "Document class '$className' used in the discriminator map of class '$owningClass' ".
            "does not exist."
        );
    }

    public static function missingFieldName($className)
    {
        return new self("The Document class '$className' field mapping misses the 'fieldName' attribute.");
    }

    public static function classIsNotAValidDocument($className)
    {
        return new self('Class '.$className.' is not a valid document or mapped super class.');
    }

    /**
     * Exception for reflection exceptions - adds the document name,
     * because there might be long classnames that will be shortened
     * within the stacktrace
     *
     * @param string $document The document's name
     * @param \ReflectionException $previousException
     */
    public static function reflectionFailure($document, \ReflectionException $previousException)
    {
        return new self('An error occurred in ' . $document, 0, $previousException);
    }

    public static function identifierRequired($documentName)
    {
        return new self("No identifier/primary key specified for Document '$documentName'."
                . " Every Document must have an identifier/primary key.");
    }

    public static function missingIdentifierField($className, $fieldName)
    {
        return new self("The identifier $fieldName is missing for a query of " . $className);
    }
}
