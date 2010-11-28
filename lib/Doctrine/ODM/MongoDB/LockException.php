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

namespace Doctrine\ODM\MongoDB;

/**
 * LockException
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @since 2.0
 */
class LockException extends MongoDBException
{
    private $document;

    public function __construct($msg, $document = null)
    {
        parent::__construct($msg);
        $this->document = $document;
    }

    /**
     * Gets the document that caused the exception.
     *
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    public static function lockFailed($document)
    {
        return new self('A lock failed on a document.', $document);
    }

    public static function lockFailedVersionMissmatch($document, $expectedLockVersion, $actualLockVersion)
    {
        return new self('The optimistic lock failed, version ' . $expectedLockVersion . ' was expected, but is actually '.$actualLockVersion, $document);
    }

    public static function notVersioned($documentName)
    {
        return new self('Document ' . $documentName . ' is not versioned.');
    }

    public static function invalidLockFieldType($type)
    {
        return new self('Invalid lock field type '.$type.'. Lock field must be int.');
    }

    public static function invalidVersionFieldType($type)
    {
        return new self('Invalid version field type '.$type.'. Version field must be int or date.');
    }
}