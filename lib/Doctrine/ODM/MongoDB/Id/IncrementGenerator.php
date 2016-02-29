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

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * IncrementGenerator is responsible for generating auto increment identifiers. It uses
 * a collection and generates the next id by using $inc on a field named "current_id".
 *
 * The 'collection' property determines which collection name is used to store the
 * id values. If not specified it defaults to 'doctrine_increment_ids'.
 *
 * The 'key' property determines the document ID used to store the id values in the
 * collection. If not specified it defaults to the name of the collection for the
 * document.
 *
 * @since       1.0
 */
class IncrementGenerator extends AbstractIdGenerator
{
    protected $collection = null;
    protected $key = null;

    public function setCollection($collection)
    {
        $this->collection = $collection;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    /** @inheritDoc */
    public function generate(DocumentManager $dm, $document)
    {
        $className = get_class($document);
        $db = $dm->getDocumentDatabase($className);

        $coll = $this->collection ?: 'doctrine_increment_ids';
        $key = $this->key ?: $dm->getDocumentCollection($className)->getName();

        $query = array('_id' => $key);
        $newObj = array('$inc' => array('current_id' => 1));

        $command = array();
        $command['findandmodify'] = $coll;
        $command['query'] = $query;
        $command['update'] = $newObj;
        $command['upsert'] = true;
        $command['new'] = true;
        $result = $db->command($command);
        return $result['value']['current_id'];
    }
}
