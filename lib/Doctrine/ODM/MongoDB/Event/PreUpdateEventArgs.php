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

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class that holds event arguments for a preUpdate event.
 *
 * @since 1.0
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /**
     * @var array
     */
    private $documentChangeSet;

    /**
     * Constructor.
     *
     * @param object          $document
     * @param DocumentManager $dm
     * @param array           $changeSet
     */
    public function __construct($document, DocumentManager $dm, array $changeSet)
    {
        parent::__construct($document, $dm);
        $this->documentChangeSet = $changeSet;
    }

    /**
     * Retrieves the document changeset.
     *
     * @return array
     */
    public function getDocumentChangeSet()
    {
        return $this->documentChangeSet;
    }

    /**
     * Checks if field has a changeset.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasChangedField($field)
    {
        return isset($this->documentChangeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @param string $field
     * @return mixed
     */
    public function getOldValue($field)
    {
        $this->assertValidField($field);

        return $this->documentChangeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @param string $field
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidField($field);

        return $this->documentChangeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param string $field
     * @param mixed  $value
     */
    public function setNewValue($field, $value)
    {
        $this->assertValidField($field);

        $this->documentChangeSet[$field][1] = $value;
        $this->getDocumentManager()->getUnitOfWork()->setDocumentChangeSet($this->getDocument(), $this->documentChangeSet);
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @param string $field
     * @throws \InvalidArgumentException if the field has no changeset
     */
    private function assertValidField($field)
    {
        if ( ! isset($this->documentChangeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the document "%s" in PreUpdateEventArgs.',
                $field,
                get_class($this->getDocument())
            ));
        }
    }
}
