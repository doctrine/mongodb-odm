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

namespace Doctrine\ODM\MongoDB\Tools;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

/**
 * The DisconnectedClassMetadataFactory is used to create ClassMetadata objects
 * that do not require the document class actually exist. This allows us to
 * load some mapping information and use it to do things like generate code
 * from the mapping information.
 *
 * @since   1.0
 */
class DisconnectedClassMetadataFactory extends ClassMetadataFactory
{
    /**
     * @override
     */
    protected function newClassMetadataInstance($className)
    {
        $metadata = new ClassMetadataInfo($className);
        if (strpos($className, "\\") !== false) {
            $metadata->namespace = strrev(substr( strrev($className), strpos(strrev($className), "\\")+1 ));
        } else {
            $metadata->namespace = '';
        }
        return $metadata;
    }

    /**
     * @override
     */
    protected function getParentClasses($name)
    {
        return array();
    }

    /**
     * @override
     */
    protected function validateIdentifier($class)
    {
        // do nothing as the DisconnectedClassMetadataFactory cannot validate an inherited id
    }
}
