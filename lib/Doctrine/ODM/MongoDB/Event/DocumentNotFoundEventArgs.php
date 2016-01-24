<?php
/*
 *  $Id$
 *
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

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Provides event arguments for the documentNotFound event.
 *
 * @since 1.1
 */
class DocumentNotFoundEventArgs extends LifecycleEventArgs
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var bool
     */
    private $disableException = false;

    /**
     * Constructor.
     *
     * @param object $document
     * @param DocumentManager $dm
     * @param string $identifier
     */
    public function __construct($document, DocumentManager $dm, $identifier)
    {
        parent::__construct($document, $dm);
        $this->identifier = $identifier;
    }

    /**
     * Retrieve associated identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Indicates whether the proxy initialization exception is disabled.
     *
     * @return bool
     */
    public function isExceptionDisabled()
    {
        return $this->disableException;
    }

    /**
     * Disable the throwing of an exception
     *
     * This method indicates to the proxy initializer that the missing document
     * has been handled and no exception should be thrown. This can't be reset.
     *
     * @param bool $disableException
     */
    public function disableException($disableException = true)
    {
        $this->disableException = $disableException;
    }
}
