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

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\QueryExpressionVisitor;
use Doctrine\Common\Collections\Collection;

class LazyCriteriaCollection extends AbstractLazyCollection implements Selectable
{
    /**
     * @var Builder
     */
    protected $queryBuilder;

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @var int|null
     */
    private $count;

    /**
     * @param Builder $queryBuilder
     * @param Criteria $criteria
     */
    public function __construct(Builder $queryBuilder, Criteria $criteria)
    {
        $this->queryBuilder = $queryBuilder;
        $this->criteria     = $criteria;
    }

    /**
     * Do an efficient count on the collection
     *
     * @return int|null
     */
    public function count()
    {
        if ($this->isInitialized()) {
            return $this->collection->count();
        }

        // Return cached result in case count query was already executed
        if ($this->count !== null) {
            return $this->count;
        }

        $cursor = $this->getQuery()->execute();

        return $this->count = $cursor->count(true);
    }

    /**
     * check if collection is empty without loading it
     *
     * @return bool TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty()
    {
        if ($this->isInitialized()) {
            return $this->collection->isEmpty();
        }

        return ! $this->count();
    }

    /**
     * @return Query\Query
     */
    protected function getQuery()
    {
        $queryBuilder = $this->queryBuilder;

        $visitor = new QueryExpressionVisitor($queryBuilder);

        if ($this->criteria->getWhereExpression() !== null) {
            $expr = $visitor->dispatch($this->criteria->getWhereExpression());
            $queryBuilder->setQueryArray($expr->getQuery());
        }

        if ($this->criteria->getMaxResults() !== null) {
            $queryBuilder->limit($this->criteria->getMaxResults());
        }

        if ($this->criteria->getFirstResult() !== null) {
            $queryBuilder->skip($this->criteria->getFirstResult());
        }

        if ($this->criteria->getOrderings() !== null) {
            $queryBuilder->sort($this->criteria->getOrderings());
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @throws MongoDBException
     */
    protected function doInitialize()
    {
        $elements = $this->getQuery()->execute()->toArray(false);

        $this->collection = new ArrayCollection($elements);
    }

    /**
     * @param Criteria $criteria
     * @return Collection
     */
    public function matching(Criteria $criteria)
    {
        if (!$this->isInitialized()) {
            $this->initialize();
        }

        return $this->collection->matching($criteria);
    }
}
