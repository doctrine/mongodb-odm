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
 * FindQuery
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class FindQuery extends AbstractQuery
{
    protected $reduce;
    protected $select = array();
    protected $query;
    protected $hydrate;
    protected $limit;
    protected $skip;
    protected $sort;
    protected $immortal;
    protected $slaveOkay;
    protected $snapshot;
    protected $hints = array();

    public function setReduce($reduce)
    {
        $this->reduce = $reduce;
    }

    public function setSelect($select)
    {
        $this->select = $select;
    }

    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    public function setHydrate($hydrate)
    {
        $this->hydrate = $hydrate;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function setSkip($skip)
    {
        $this->skip = $skip;
    }

    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    public function setImmortal($immortal)
    {
        $this->immortal = $immortal;
    }

    public function setSlaveOkay($slaveOkay)
    {
        $this->slaveOkay = $slaveOkay;
    }

    public function setSnapshot($snapshot)
    {
        $this->snapshot = $snapshot;
    }

    public function setHints(array $hints)
    {
        $this->hints = $hints;
    }

    public function execute(array $options = array())
    {
        if ($this->reduce) {
            $this->query[$this->cmd . 'where'] = $this->reduce;
        }
        $cursor = $this->dm->getDocumentCollection($this->class->name)->find($this->query, $this->select, $options);
        $cursor = new MongoCursor($this->dm, $this->dm->getUnitOfWork(), $this->dm->getHydrator(), $this->class, $this->dm->getConfiguration(), $cursor);
        $cursor->hydrate($this->hydrate);
        $cursor->limit($this->limit);
        $cursor->skip($this->skip);
        $cursor->sort($this->sort);
        $cursor->immortal($this->immortal);
        $cursor->slaveOkay($this->slaveOkay);
        if ($this->snapshot) {
            $cursor->snapshot();
        }
        foreach ($this->hints as $keyPattern) {
            $cursor->hint($keyPattern);
        }
        return $cursor;
    }
}