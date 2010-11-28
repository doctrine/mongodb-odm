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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\MongoCursor;

/**
 * InsertQuery
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MapReduceQuery extends AbstractQuery
{
    protected $map;
    protected $reduce;
    protected $options = array();
    protected $query = array();

    public function setMap($map)
    {
        $this->map = $map;
    }

    public function setReduce($reduce)
    {
        $this->reduce = $reduce;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    public function execute(array $options = array())
    {
        $db = $this->dm->getDocumentDB($this->class->name);
        if (is_string($this->map)) {
            $this->map = new \MongoCode($this->map);
        }
        if (is_string($this->reduce)) {
            $this->reduce = new \MongoCode($this->reduce);
        }
        $command = array(
            'mapreduce' => $this->class->getCollection(),
            'map' => $this->map,
            'reduce' => $this->reduce,
            'query' => $this->query
        );
        $command = array_merge($command, $options);
        $result = $db->command($command);
        if ( ! $result['ok']) {
            throw new \RuntimeException($result['errmsg']);
        }
        $cursor = $db->selectCollection($result['result'])->find();
        $cursor = new MongoCursor($this->dm, $this->dm->getUnitOfWork(), $this->dm->getHydrator(), $this->class, $this->dm->getConfiguration(), $cursor);
        $cursor->hydrate(false);
        return $cursor;
    }
}