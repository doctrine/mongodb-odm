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

use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * MongoDB ODM PersistentCollection Exception.
 *
 * @since 1.1
 */
class PersistentCollectionException extends MongoDBException
{
    public static function directoryNotWritable()
    {
        return new self('Your PersistentCollection directory must be writable.');
    }

    public static function directoryRequired()
    {
        return new self('You must configure a PersistentCollection directory. See docs for details.');
    }

    public static function namespaceRequired()
    {
        return new self('You must configure a PersistentCollection namespace. See docs for details');
    }

    /**
     * @param string          $className
     * @param string          $methodName
     * @param string          $parameterName
     * @param \Exception|null $previous
     *
     * @return self
     */
    public static function invalidParameterTypeHint(
        $className,
        $methodName,
        $parameterName,
        \Exception $previous = null
    ) {
        return new self(
            sprintf(
                'The type hint of parameter "%s" in method "%s" in class "%s" is invalid.',
                $parameterName,
                $methodName,
                $className
            ),
            0,
            $previous
        );
    }

    /**
     * @param $className
     * @param $methodName
     * @param \Exception|null $previous
     *
     * @return self
     */
    public static function invalidReturnTypeHint($className, $methodName, \Exception $previous = null)
    {
        return new self(
            sprintf(
                'The return type of method "%s" in class "%s" is invalid.',
                $methodName,
                $className
            ),
            0,
            $previous
        );
    }
}
