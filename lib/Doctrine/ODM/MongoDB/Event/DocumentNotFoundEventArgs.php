<?php

declare(strict_types=1);

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
 */
class DocumentNotFoundEventArgs extends LifecycleEventArgs
{
    /** @var mixed */
    private $identifier;

    /** @var bool */
    private $disableException = false;

    /**
     * @param mixed $identifier
     */
    public function __construct(object $document, DocumentManager $dm, $identifier)
    {
        parent::__construct($document, $dm);
        $this->identifier = $identifier;
    }

    /**
     * Retrieve associated identifier.
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Indicates whether the proxy initialization exception is disabled.
     */
    public function isExceptionDisabled(): bool
    {
        return $this->disableException;
    }

    /**
     * Disable the throwing of an exception
     *
     * This method indicates to the proxy initializer that the missing document
     * has been handled and no exception should be thrown. This can't be reset.
     */
    public function disableException(bool $disableException = true): void
    {
        $this->disableException = $disableException;
    }
}
