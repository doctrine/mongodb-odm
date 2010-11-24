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

/**
 * FindAndRemoveQuery
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class FindAndRemoveQuery extends AbstractQuery
{
    protected $select = array();
    protected $query = array();
    protected $sort;
    protected $upsert;
    protected $new;
    protected $limit;

    public function setSelect(array $select)
    {
        $this->select = $select;
    }

    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    public function execute(array $options = array())
    {
        $command = array();
        $command['findandmodify'] = $this->dm->getDocumentCollection($this->class->name)->getName();
        if ($this->query) {
            $command['query'] = $this->query;
        }
        if ($this->sort) {
            $command['sort'] = $this->sort;
        }
        if ($this->select) {
            $command['fields'] = $this->select;
        }
        $command['remove'] = true;
        if ($this->limit) {
            $command['num'] = $this->limit;
        }
        $result = $this->dm->getDocumentDB($this->class->name)
            ->command($command);
        if (isset($result['value'])) {
            return $this->dm->getUnitOfWork()->getOrCreateDocument(
                $this->class->name, $result['value']
            );
        }
        return $result;
    }
}