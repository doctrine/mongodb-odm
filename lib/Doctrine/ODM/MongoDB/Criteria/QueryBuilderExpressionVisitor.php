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

namespace Doctrine\ODM\MongoDB\Criteria;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\MongoDB\Query\Builder;

/**
 * @since       1.0
 * @author      Antoine Hedgecock <antoine@pmg.se>
 * @author      Michaël Gallego <mic.gallego@gmail.com>
 */
class QueryBuilderExpressionVisitor extends ExpressionVisitor
{
    /**
     * Map Criteria API expressions to MongoDB ones
     *
     * @var array
     */
    private $comparisonTable = array(
        '='   => 'equals',
        '<>'  => 'notEqual',
        '<'   => 'lt',
        '<='  => 'lte',
        '>'   => 'gt',
        '>='  => 'gte',
        'IN'  => 'in',
        'NIN' => 'notIn'
    );

    /**
     * @var Builder
     */
    protected $queryBuilder;

    /**
     * @param Builder $queryBuilder
     */
    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $operator = $comparison->getOperator();
        $field    = $comparison->getField();
        $value    = $this->dispatch($comparison->getValue());

        if (!isset($this->comparisonTable[$operator])) {
            throw RuntimeException::unknownComparisonOperator($operator);
        }

        $method = $this->comparisonTable[$operator];

        $expr = $this->queryBuilder->expr();
        $expr->field($field)
             ->{$method}($value);

        return $expr;
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $compositeExpr)
    {
        $expr = $this->queryBuilder->expr();

        foreach ($compositeExpr->getExpressionList() as $child) {
            $child = $this->dispatch($child);

            switch ($compositeExpr->getType()) {
                case CompositeExpression::TYPE_AND:
                    $expr->addAnd($child);
                    break;

                case CompositeExpression::TYPE_OR:
                    $expr->addOr($child);
                    break;
            }
        }

        return $expr;
    }
}