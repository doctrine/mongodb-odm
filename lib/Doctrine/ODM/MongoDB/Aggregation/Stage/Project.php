<?php

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Fluent interface for adding a $project stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.2
 */
class Project extends Operator
{
    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$project' => $this->expr->getExpression()
        ];
    }

    /**
     * Returns the average value of the numeric values that result from applying
     * a specified expression to each document in a group of documents that
     * share the same group by key. Ignores nun-numeric values.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/avg/
     * @see Expr::avg
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2, ... Additional expressions
     * @return $this
     *
     * @since 1.3
     */
    public function avg($expression1/* , $expression2, ... */)
    {
        $this->expr->avg(func_num_args() === 1 ? $expression1 : func_get_args());

        return $this;
    }

    /**
     * Shorthand method to exclude the _id field.
     *
     * @deprecated Deprecated in 1.5, please use {@link excludeFields()}.
     * @param bool $exclude
     * @return $this
     */
    public function excludeIdField($exclude = true)
    {
        @trigger_error(__METHOD__ . ' has been deprecated in favor of excludeFields.', E_USER_DEPRECATED);

        return $this->field('_id')->expression( ! $exclude);
    }

    /**
     * Shorthand method to define which fields to be included.
     *
     * @param array $fields
     * @return $this
     */
    public function includeFields(array $fields)
    {
        foreach ($fields as $fieldName) {
            $this->field($fieldName)->expression(true);
        }

        return $this;
    }

    /**
     * Shorthand method to define which fields to be excluded.
     *
     * If you specify the exclusion of a field other than _id, you cannot employ
     * any other $project specification forms.
     *
     * @since 1.5
     * @param array $fields
     * @return $this
     */
    public function excludeFields(array $fields)
    {
        foreach ($fields as $fieldName) {
            $this->field($fieldName)->expression(false);
        }

        return $this;
    }

    /**
     * Returns the highest value that results from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/max/
     * @see Expr::max
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2, ... Additional expressions
     * @return $this
     *
     * @since 1.3
     */
    public function max($expression1/* , $expression2, ... */)
    {
        $this->expr->max(func_num_args() === 1 ? $expression1 : func_get_args());

        return $this;
    }

    /**
     * Returns the lowest value that results from applying an expression to each
     * document in a group of documents that share the same group by key.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/min/
     * @see Expr::min
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2, ... Additional expressions
     * @return $this
     *
     * @since 1.3
     */
    public function min($expression1/* , $expression2, ... */)
    {
        $this->expr->min(func_num_args() === 1 ? $expression1 : func_get_args());

        return $this;
    }

    /**
     * Calculates the population standard deviation of the input values.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/stdDevPop/
     * @see Expr::stdDevPop
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2, ... Additional expressions
     * @return $this
     *
     * @since 1.3
     */
    public function stdDevPop($expression1/* , $expression2, ... */)
    {
        $this->expr->stdDevPop(func_num_args() === 1 ? $expression1 : func_get_args());

        return $this;
    }

    /**
     * Calculates the sample standard deviation of the input values.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/stdDevSamp/
     * @see Expr::stdDevSamp
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2, ... Additional expressions
     * @return $this
     *
     * @since 1.3
     */
    public function stdDevSamp($expression1/* , $expression2, ... */)
    {
        $this->expr->stdDevSamp(func_num_args() === 1 ? $expression1 : func_get_args());

        return $this;
    }

    /**
     * Calculates and returns the sum of all the numeric values that result from
     * applying a specified expression to each document in a group of documents
     * that share the same group by key. Ignores nun-numeric values.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/sum/
     * @see Expr::sum
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2, ... Additional expressions
     * @return $this
     *
     * @since 1.3
     */
    public function sum($expression1/* , $expression2, ... */)
    {
        $this->expr->sum(func_num_args() === 1 ? $expression1 : func_get_args());

        return $this;
    }
}
