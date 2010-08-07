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

namespace Doctrine\ODM\MongoDB\Collection;

use Doctrine\Common\Collections\Collection,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Proxy\Proxy,
    Doctrine\ODM\MongoDB\DocumentManager,
    Closure;

/**
 * A PersistentReferenceCollection represents a collection of referenced documents.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
final class PersistentReferenceCollection extends AbstractPersistentCollection
{
    /**
     * The DocumentManager that manages the persistence of the collection.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $_dm;

    /**
     * Mongo command prefix
     * @var string
     */
    protected $_cmd;

    public function __construct(DocumentManager $dm, Collection $coll)
    {
        $this->_coll = $coll;
        $this->_dm = $dm;
        $this->_cmd = $dm->getConfiguration()->getMongoCmd();
    }

    /**
     * @inheritdoc
     */
    protected function _initialize()
    {
        if ( ! $this->_initialized) {
            $groupedIds = array();
            foreach ($this->_coll as $document) {
                $class = $this->_dm->getClassMetadata(get_class($document));
                $ids[$class->name][] = $class->getIdentifierObject($document);
            }

            foreach ($groupedIds as $className => $ids) {
                $collection = $this->_dm->getDocumentCollection($className);
                $data = $collection->find(array('_id' => array($this->_cmd . 'in' => $ids)));
                $hints = array(Query::HINT_REFRESH => Query::HINT_REFRESH);
                foreach ($data as $id => $documentData) {
                    $document = $this->_dm->getUnitOfWork()->getOrCreateDocument($this->_typeClass->name, $documentData, $hints);
                    if ($document instanceof Proxy) {
                        $document->__isInitialized__ = true;
                        unset($document->__dm);
                        unset($document->__identifier);
                    }
                }
            }

            $this->_initialized = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->_initialize();
        $result = $this->_coll->clear();
        if ($this->_mapping->isOwningSide) {
            $this->_changed();
            $this->_dm->getUnitOfWork()->scheduleCollectionDeletion($this);
        }
        return $result;
    }
}