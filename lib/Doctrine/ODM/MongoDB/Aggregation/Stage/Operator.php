<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

use function func_get_args;

/**
 * Fluent interface for adding operators to aggregation stages.
 *
 * @method $this switch()
 * @method $this case(mixed|Expr $expression)
 * @method $this then(mixed|Expr $expression)
 * @method $this default(mixed|Expr $expression)
 */
abstract class Operator extends Stage
{
    /** @var Expr */
    protected $expr;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->expr = $builder->expr();
    }

    public function __call(string $method, array $args): self
    {
        $this->expr->$method(...$args);

        return $this;
    }

    /**
     * Returns the absolute value of a number.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/abs/
     * @see Expr::abs
     *
     * @param mixed|Expr $number
     */
    public function abs($number): self
    {
        $this->expr->abs($number);

        return $this;
    }

    /**
     * Adds numbers together or adds numbers and a date. If one of the arguments
     * is a date, $add treats the other arguments as milliseconds to add to the
     * date.
     *
     * The arguments can be any valid expression as long as they resolve to either all numbers or to numbers and a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/add/
     * @see Expr::add
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function add($expression1, $expression2, ...$expressions): self
    {
        $this->expr->add(...func_get_args());

        return $this;
    }

    /**
     * Add one or more $and clauses to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/and/
     * @see Expr::addAnd
     *
     * @param array|Expr $expression
     * @param array|Expr ...$expressions
     */
    public function addAnd($expression, ...$expressions): self
    {
        $this->expr->addAnd(...func_get_args());

        return $this;
    }

    /**
     * Add one or more $or clauses to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/or/
     * @see Expr::addOr
     *
     * @param array|Expr $expression
     * @param array|Expr ...$expressions
     */
    public function addOr($expression, ...$expressions): self
    {
        $this->expr->addOr(...func_get_args());

        return $this;
    }

    /**
     * Evaluates an array as a set and returns true if no element in the array
     * is false. Otherwise, returns false. An empty array returns true.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/allElementsTrue/
     * @see Expr::allElementsTrue
     *
     * @param mixed|Expr $expression
     */
    public function allElementsTrue($expression): self
    {
        $this->expr->allElementsTrue($expression);

        return $this;
    }

    /**
     * Evaluates an array as a set and returns true if any of the elements are
     * true and false otherwise. An empty array returns false.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/anyElementTrue/
     * @see Expr::anyElementTrue
     *
     * @param array|Expr $expression
     */
    public function anyElementTrue($expression): self
    {
        $this->expr->anyElementTrue($expression);

        return $this;
    }

    /**
     * Returns the element at the specified array index.
     *
     * The <array> expression can be any valid expression as long as it resolves
     * to an array.
     * The <idx> expression can be any valid expression as long as it resolves
     * to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayElemAt/
     * @see Expr::arrayElemAt
     *
     * @param mixed|Expr $array
     * @param mixed|Expr $index
     */
    public function arrayElemAt($array, $index): self
    {
        $this->expr->arrayElemAt($array, $index);

        return $this;
    }

    /**
     * Returns the smallest integer greater than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ceil/
     * @see Expr::ceil
     *
     * @param mixed|Expr $number
     */
    public function ceil($number): self
    {
        $this->expr->ceil($number);

        return $this;
    }

    /**
     * Compares two values and returns:
     * -1 if the first value is less than the second.
     * 1 if the first value is greater than the second.
     * 0 if the two values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cmp/
     * @see Expr::cmp
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function cmp($expression1, $expression2): self
    {
        $this->expr->cmp($expression1, $expression2);

        return $this;
    }

    /**
     * Concatenates strings and returns the concatenated string.
     *
     * The arguments can be any valid expression as long as they resolve to
     * strings. If the argument resolves to a value of null or refers to a field
     * that is missing, $concat returns null.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/concat/
     * @see Expr::concat
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function concat($expression1, $expression2, ...$expressions): self
    {
        $this->expr->concat(...func_get_args());

        return $this;
    }

    /**
     * Concatenates arrays to return the concatenated array.
     *
     * The <array> expressions can be any valid expression as long as they
     * resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/concatArrays/
     * @see Expr::concatArrays
     *
     * @param mixed|Expr $array1
     * @param mixed|Expr $array2
     * @param mixed|Expr ...$arrays Additional expressions
     */
    public function concatArrays($array1, $array2, ...$arrays): self
    {
        $this->expr->concatArrays(...func_get_args());

        return $this;
    }

    /**
     * Evaluates a boolean expression to return one of the two specified return
     * expressions.
     *
     * The arguments can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cond/
     * @see Expr::cond
     *
     * @param mixed|Expr $if
     * @param mixed|Expr $then
     * @param mixed|Expr $else
     */
    public function cond($if, $then, $else): self
    {
        $this->expr->cond($if, $then, $else);

        return $this;
    }

    /**
     * Converts a date object to a string according to a user-specified format.
     *
     * The format string can be any string literal, containing 0 or more format
     * specifiers.
     * The date argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToString/
     * @see Expr::dateToString
     *
     * @param string     $format
     * @param mixed|Expr $expression
     */
    public function dateToString($format, $expression): self
    {
        $this->expr->dateToString($format, $expression);

        return $this;
    }

    /**
     * Returns the day of the month for a date as a number between 1 and 31.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfMonth/
     * @see Expr::dayOfMonth
     *
     * @param mixed|Expr $expression
     */
    public function dayOfMonth($expression): self
    {
        $this->expr->dayOfMonth($expression);

        return $this;
    }

    /**
     * Returns the day of the week for a date as a number between 1 (Sunday) and
     * 7 (Saturday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfWeek/
     * @see Expr::dayOfWeek
     *
     * @param mixed|Expr $expression
     */
    public function dayOfWeek($expression): self
    {
        $this->expr->dayOfWeek($expression);

        return $this;
    }

    /**
     * Returns the day of the year for a date as a number between 1 and 366.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfYear/
     * @see Expr::dayOfYear
     *
     * @param mixed|Expr $expression
     */
    public function dayOfYear($expression): self
    {
        $this->expr->dayOfYear($expression);

        return $this;
    }

    /**
     * Divides one number by another and returns the result. The first argument
     * is divided by the second argument.
     *
     * The arguments can be any valid expression as long as the resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/divide/
     * @see Expr::divide
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function divide($expression1, $expression2): self
    {
        $this->expr->divide($expression1, $expression2);

        return $this;
    }

    /**
     * Compares two values and returns whether they are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/eq/
     * @see Expr::eq
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function eq($expression1, $expression2): self
    {
        $this->expr->eq($expression1, $expression2);

        return $this;
    }

    /**
     * Raises Euler’s number to the specified exponent and returns the result.
     *
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/exp/
     * @see Expr::exp
     *
     * @param mixed|Expr $exponent
     */
    public function exp($exponent): self
    {
        $this->expr->exp($exponent);

        return $this;
    }

    /**
     * Used to use an expression as field value. Can be any expression
     *
     * @see https://docs.mongodb.com/manual/meta/aggregation-quick-reference/#aggregation-expressions
     * @see Expr::expression
     *
     * @param mixed|Expr $value
     */
    public function expression($value)
    {
        $this->expr->expression($value);

        return $this;
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Expr::field
     */
    public function field(string $fieldName)
    {
        $this->expr->field($fieldName);

        return $this;
    }

    /**
     * Selects a subset of the array to return based on the specified condition.
     *
     * Returns an array with only those elements that match the condition. The
     * returned elements are in the original order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/filter/
     * @see Expr::filter
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $as
     * @param mixed|Expr $cond
     */
    public function filter($input, $as, $cond): self
    {
        $this->expr->filter($input, $as, $cond);

        return $this;
    }

    /**
     * Returns the largest integer less than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/floor/
     * @see Expr::floor
     *
     * @param mixed|Expr $number
     */
    public function floor($number): self
    {
        $this->expr->floor($number);

        return $this;
    }

    /**
     * Compares two values and returns:
     * true when the first value is greater than the second value.
     * false when the first value is less than or equivalent to the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gt/
     * @see Expr::gt
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function gt($expression1, $expression2): self
    {
        $this->expr->gt($expression1, $expression2);

        return $this;
    }

    /**
     * Compares two values and returns:
     * true when the first value is greater than or equivalent to the second value.
     * false when the first value is less than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gte/
     * @see Expr::gte
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function gte($expression1, $expression2): self
    {
        $this->expr->gte($expression1, $expression2);

        return $this;
    }

    /**
     * Returns the hour portion of a date as a number between 0 and 23.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/hour/
     * @see Expr::hour
     *
     * @param mixed|Expr $expression
     */
    public function hour($expression): self
    {
        $this->expr->hour($expression);

        return $this;
    }

    /**
     * Returns a boolean indicating whether a specified value is in an array.
     *
     * Unlike the $in query operator, the aggregation $in operator does not
     * support matching by regular expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/in/
     * @see Expr::in
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $arrayExpression
     */
    public function in($expression, $arrayExpression): self
    {
        $this->expr->in($expression, $arrayExpression);

        return $this;
    }

    /**
     * Searches an array for an occurrence of a specified value and returns the
     * array index (zero-based) of the first occurrence. If the value is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfArray/
     * @see Expr::indexOfArray
     *
     * @param mixed|Expr $arrayExpression  Can be any valid expression as long as it resolves to an array.
     * @param mixed|Expr $searchExpression Can be any valid expression.
     * @param mixed|Expr $start            Optional. An integer, or a number that can be represented as integers (such as 2.0), that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param mixed|Expr $end              An integer, or a number that can be represented as integers (such as 2.0), that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfArray($arrayExpression, $searchExpression, $start, $end);

        return $this;
    }

    /**
     * Searches a string for an occurrence of a substring and returns the UTF-8
     * byte index (zero-based) of the first occurrence. If the substring is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfBytes/
     *
     * @param mixed|Expr      $stringExpression    Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr      $substringExpression Can be any valid expression as long as it resolves to a string.
     * @param string|int|null $start               An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param string|int|null $end                 An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfBytes($stringExpression, $substringExpression, $start, $end);

        return $this;
    }

    /**
     * Searches a string for an occurrence of a substring and returns the UTF-8
     * code point index (zero-based) of the first occurrence. If the substring is
     * not found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfCP/
     *
     * @param mixed|Expr      $stringExpression    Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr      $substringExpression Can be any valid expression as long as it resolves to a string.
     * @param string|int|null $start               An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param string|int|null $end                 An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfCP($stringExpression, $substringExpression, $start, $end);

        return $this;
    }

    /**
     * Evaluates an expression and returns the value of the expression if the
     * expression evaluates to a non-null value. If the expression evaluates to
     * a null value, including instances of undefined values or missing fields,
     * returns the value of the replacement expression.
     *
     * The arguments can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ifNull/
     * @see Expr::ifNull
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $replacementExpression
     */
    public function ifNull($expression, $replacementExpression): self
    {
        $this->expr->ifNull($expression, $replacementExpression);

        return $this;
    }

    /**
     * Determines if the operand is an array. Returns a boolean.
     *
     * The <expression> can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isArray/
     * @see Expr::isArray
     *
     * @param mixed|Expr $expression
     */
    public function isArray($expression): self
    {
        $this->expr->isArray($expression);

        return $this;
    }

    /**
     * Returns the weekday number in ISO 8601 format, ranging from 1 (for Monday)
     * to 7 (for Sunday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoDayOfWeek/
     *
     * @param mixed|Expr $expression
     */
    public function isoDayOfWeek($expression): self
    {
        $this->expr->isoDayOfWeek($expression);

        return $this;
    }

    /**
     * Returns the week number in ISO 8601 format, ranging from 1 to 53.
     *
     * Week numbers start at 1 with the week (Monday through Sunday) that
     * contains the year’s first Thursday.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoWeek/
     *
     * @param mixed|Expr $expression
     */
    public function isoWeek($expression): self
    {
        $this->expr->isoWeek($expression);

        return $this;
    }

    /**
     * Returns the year number in ISO 8601 format.
     *
     * The year starts with the Monday of week 1 (ISO 8601) and ends with the
     * Sunday of the last week (ISO 8601).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoWeek/
     *
     * @param mixed|Expr $expression
     */
    public function isoWeekYear($expression): self
    {
        $this->expr->isoWeekYear($expression);

        return $this;
    }

    /**
     * Binds variables for use in the specified expression, and returns the
     * result of the expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/let/
     * @see Expr::let
     *
     * @param mixed|Expr $vars Assignment block for the variables accessible in the in expression. To assign a variable, specify a string for the variable name and assign a valid expression for the value.
     * @param mixed|Expr $in   The expression to evaluate.
     */
    public function let($vars, $in): self
    {
        $this->expr->let($vars, $in);

        return $this;
    }

    /**
     * Returns a value without parsing. Use for values that the aggregation
     * pipeline may interpret as an expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/literal/
     * @see Expr::literal
     *
     * @param mixed|Expr $value
     */
    public function literal($value): self
    {
        $this->expr->literal($value);

        return $this;
    }

    /**
     * Calculates the natural logarithm ln (i.e loge) of a number and returns
     * the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     * @see Expr::ln
     *
     * @param mixed|Expr $number
     */
    public function ln($number): self
    {
        $this->expr->ln($number);

        return $this;
    }

    /**
     * Calculates the log of a number in the specified base and returns the
     * result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <base> expression can be any valid expression as long as it resolves
     * to a positive number greater than 1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     * @see Expr::log
     *
     * @param mixed|Expr $number
     * @param mixed|Expr $base
     */
    public function log($number, $base): self
    {
        $this->expr->log($number, $base);

        return $this;
    }

    /**
     * Calculates the log base 10 of a number and returns the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <base> expression can be any valid expression as long as it resolves
     * to a positive number greater than 1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     * @see Expr::log10
     *
     * @param mixed|Expr $number
     */
    public function log10($number): self
    {
        $this->expr->log10($number);

        return $this;
    }

    /**
     * Compares two values and returns:
     * true when the first value is less than the second value.
     * false when the first value is greater than or equivalent to the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lt/
     * @see Expr::lt
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function lt($expression1, $expression2): self
    {
        $this->expr->lt($expression1, $expression2);

        return $this;
    }

    /**
     * Compares two values and returns:
     * true when the first value is less than or equivalent to the second value.
     * false when the first value is greater than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lte/
     * @see Expr::lte
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function lte($expression1, $expression2): self
    {
        $this->expr->lte($expression1, $expression2);

        return $this;
    }

    /**
     * Applies an expression to each item in an array and returns an array with
     * the applied results.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/map/
     * @see Expr::map
     *
     * @param mixed|Expr $input An expression that resolves to an array.
     * @param string     $as    The variable name for the items in the input array. The in expression accesses each item in the input array by this variable.
     * @param mixed|Expr $in    The expression to apply to each item in the input array. The expression accesses the item by its variable name.
     */
    public function map($input, $as, $in): self
    {
        $this->expr->map($input, $as, $in);

        return $this;
    }

    /**
     * Returns the metadata associated with a document in a pipeline operations.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/meta/
     * @see Expr::meta
     *
     * @param mixed|Expr $metaDataKeyword
     */
    public function meta($metaDataKeyword): self
    {
        $this->expr->meta($metaDataKeyword);

        return $this;
    }

    /**
     * Returns the millisecond portion of a date as an integer between 0 and 999.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/millisecond/
     * @see Expr::millisecond
     *
     * @param mixed|Expr $expression
     */
    public function millisecond($expression): self
    {
        $this->expr->millisecond($expression);

        return $this;
    }

    /**
     * Returns the minute portion of a date as a number between 0 and 59.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minute/
     * @see Expr::minute
     *
     * @param mixed|Expr $expression
     */
    public function minute($expression): self
    {
        $this->expr->minute($expression);

        return $this;
    }

    /**
     * Divides one number by another and returns the remainder. The first
     * argument is divided by the second argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mod/
     * @see Expr::mod
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function mod($expression1, $expression2): self
    {
        $this->expr->mod($expression1, $expression2);

        return $this;
    }

    /**
     * Returns the month of a date as a number between 1 and 12.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/month/
     * @see Expr::month
     *
     * @param mixed|Expr $expression
     */
    public function month($expression): self
    {
        $this->expr->month($expression);

        return $this;
    }

    /**
     * Multiplies numbers together and returns the result.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/multiply/
     * @see Expr::multiply
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function multiply($expression1, $expression2, ...$expressions): self
    {
        $this->expr->multiply(...func_get_args());

        return $this;
    }

    /**
     * Compares two values and returns:
     * true when the values are not equivalent.
     * false when the values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ne/
     * @see Expr::ne
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function ne($expression1, $expression2): self
    {
        $this->expr->ne($expression1, $expression2);

        return $this;
    }

    /**
     * Evaluates a boolean and returns the opposite boolean value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/not/
     * @see Expr::not
     *
     * @param mixed|Expr $expression
     */
    public function not($expression): self
    {
        $this->expr->not($expression);

        return $this;
    }

    /**
     * Raises a number to the specified exponent and returns the result.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/pow/
     * @see Expr::pow
     *
     * @param mixed|Expr $number
     * @param mixed|Expr $exponent
     */
    public function pow($number, $exponent): self
    {
        $this->expr->pow($number, $exponent);

        return $this;
    }

    /**
     * Returns an array whose elements are a generated sequence of numbers.
     *
     * $range generates the sequence from the specified starting number by successively incrementing the starting number by the specified step value up to but not including the end point.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/range/
     * @see Expr::range
     *
     * @param mixed|Expr $start An integer that specifies the start of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|Expr $end   An integer that specifies the exclusive upper limit of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|Expr $step  Optional. An integer that specifies the increment value. Can be any valid expression that resolves to a non-zero integer. Defaults to 1.
     */
    public function range($start, $end, $step = 1): self
    {
        $this->expr->range($start, $end, $step);

        return $this;
    }

    /**
     * Applies an expression to each element in an array and combines them into
     * a single value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reduce/
     * @see Expr::reduce
     *
     * @param mixed|Expr $input        Can be any valid expression that resolves to an array.
     * @param mixed|Expr $initialValue The initial cumulative value set before in is applied to the first element of the input array.
     * @param mixed|Expr $in           A valid expression that $reduce applies to each element in the input array in left-to-right order. Wrap the input value with $reverseArray to yield the equivalent of applying the combining expression from right-to-left.
     */
    public function reduce($input, $initialValue, $in): self
    {
        $this->expr->reduce($input, $initialValue, $in);

        return $this;
    }

    /**
     * Accepts an array expression as an argument and returns an array with the
     * elements in reverse order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reverseArray/
     * @see Expr::reverseArray
     *
     * @param mixed|Expr $expression
     */
    public function reverseArray($expression): self
    {
        $this->expr->reverseArray($expression);

        return $this;
    }

    /**
     * Returns the second portion of a date as a number between 0 and 59, but
     * can be 60 to account for leap seconds.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/second/
     * @see Expr::second
     *
     * @param mixed|Expr $expression
     */
    public function second($expression): self
    {
        $this->expr->second($expression);

        return $this;
    }

    /**
     * Takes two sets and returns an array containing the elements that only
     * exist in the first set.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setDifference/
     * @see Expr::setDifference
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function setDifference($expression1, $expression2): self
    {
        $this->expr->setDifference($expression1, $expression2);

        return $this;
    }

    /**
     * Compares two or more arrays and returns true if they have the same
     * distinct elements and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setEquals/
     * @see Expr::setEquals
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     *
     * @return $this
     */
    public function setEquals($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setEquals(...func_get_args());

        return $this;
    }

    /**
     * Takes two or more arrays and returns an array that contains the elements
     * that appear in every input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIntersection/
     * @see Expr::setIntersection
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setIntersection($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setIntersection(...func_get_args());

        return $this;
    }

    /**
     * Takes two arrays and returns true when the first array is a subset of the
     * second, including when the first array equals the second array, and false
     * otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIsSubset/
     * @see Expr::setIsSubset
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function setIsSubset($expression1, $expression2): self
    {
        $this->expr->setIsSubset($expression1, $expression2);

        return $this;
    }

    /**
     * Takes two or more arrays and returns an array containing the elements
     * that appear in any input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setUnion/
     * @see Expr::setUnion
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setUnion($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setUnion(...func_get_args());

        return $this;
    }

    /**
     * Counts and returns the total the number of items in an array.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/size/
     * @see Expr::size
     *
     * @param mixed|Expr $expression
     */
    public function size($expression): self
    {
        $this->expr->size($expression);

        return $this;
    }

    /**
     * Returns a subset of an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/slice/
     * @see Expr::slice
     *
     * @param mixed|Expr      $array
     * @param mixed|Expr      $n
     * @param mixed|Expr|null $position
     */
    public function slice($array, $n, $position = null): self
    {
        $this->expr->slice($array, $n, $position);

        return $this;
    }

    /**
     * Divides a string into an array of substrings based on a delimiter.
     *
     * $split removes the delimiter and returns the resulting substrings as
     * elements of an array. If the delimiter is not found in the string, $split
     * returns the original string as the only element of an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/split/
     *
     * @param mixed|Expr $string    The string to be split. Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr $delimiter The delimiter to use when splitting the string expression. Can be any valid expression as long as it resolves to a string.
     */
    public function split($string, $delimiter): self
    {
        $this->expr->split($string, $delimiter);

        return $this;
    }

    /**
     * Calculates the square root of a positive number and returns the result as
     * a double.
     *
     * The argument can be any valid expression as long as it resolves to a
     * non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sqrt/
     * @see Expr::sqrt
     *
     * @param mixed|Expr $expression
     */
    public function sqrt($expression): self
    {
        $this->expr->sqrt($expression);

        return $this;
    }

    /**
     * Performs case-insensitive comparison of two strings. Returns
     * 1 if first string is “greater than” the second string.
     * 0 if the two strings are equal.
     * -1 if the first string is “less than” the second string.
     *
     * The arguments can be any valid expression as long as they resolve to strings.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strcasecmp/
     * @see Expr::strcasecmp
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function strcasecmp($expression1, $expression2): self
    {
        $this->expr->strcasecmp($expression1, $expression2);

        return $this;
    }

    /**
     * Returns the number of UTF-8 encoded bytes in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenBytes/
     *
     * @param mixed|Expr $string
     */
    public function strLenBytes($string): self
    {
        $this->expr->strLenBytes($string);

        return $this;
    }

    /**
     * Returns the number of UTF-8 code points in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenCP/
     *
     * @param mixed|Expr $string
     */
    public function strLenCP($string): self
    {
        $this->expr->strLenCP($string);

        return $this;
    }

    /**
     * Returns a substring of a string, starting at a specified index position
     * and including the specified number of characters. The index is zero-based.
     *
     * The arguments can be any valid expression as long as long as the first argument resolves to a string, and the second and third arguments resolve to integers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substr/
     * @see Expr::substr
     *
     * @param mixed|Expr $string
     * @param mixed|Expr $start
     * @param mixed|Expr $length
     */
    public function substr($string, $start, $length): self
    {
        $this->expr->substr($string, $start, $length);

        return $this;
    }

    /**
     * Returns the substring of a string.
     *
     * The substring starts with the character at the specified UTF-8 byte index
     * (zero-based) in the string and continues for the number of bytes
     * specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substrBytes/
     *
     * @param mixed|Expr $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr $start  Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|Expr $count  Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     */
    public function substrBytes($string, $start, $count): self
    {
        $this->expr->substrBytes($string, $start, $count);

        return $this;
    }

    /**
     * Returns the substring of a string.
     *
     * The substring starts with the character at the specified UTF-8 code point
     * (CP) index (zero-based) in the string for the number of code points
     * specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substrBytes/
     *
     * @param mixed|Expr $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr $start  Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|Expr $count  Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     */
    public function substrCP($string, $start, $count): self
    {
        $this->expr->substrCP($string, $start, $count);

        return $this;
    }

    /**
     * Subtracts two numbers to return the difference. The second argument is
     * subtracted from the first argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers and/or dates.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/subtract/
     * @see Expr::subtract
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function subtract($expression1, $expression2): self
    {
        $this->expr->subtract($expression1, $expression2);

        return $this;
    }

    /**
     * Converts a string to lowercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLower/
     * @see Expr::toLower
     *
     * @param mixed|Expr $expression
     */
    public function toLower($expression): self
    {
        $this->expr->toLower($expression);

        return $this;
    }

    /**
     * Converts a string to uppercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toUpper/
     * @see Expr::toUpper
     *
     * @param mixed|Expr $expression
     */
    public function toUpper($expression): self
    {
        $this->expr->toUpper($expression);

        return $this;
    }

    /**
     * Truncates a number to its integer.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trunc/
     * @see Expr::trunc
     *
     * @param mixed|Expr $number
     */
    public function trunc($number): self
    {
        $this->expr->trunc($number);

        return $this;
    }

    /**
     * Returns a string that specifies the BSON type of the argument.
     *
     * The argument can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/type/
     *
     * @param mixed|Expr $expression
     */
    public function type($expression): self
    {
        $this->expr->type($expression);

        return $this;
    }

    /**
     * Returns the week of the year for a date as a number between 0 and 53.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/week/
     * @see Expr::week
     *
     * @param mixed|Expr $expression
     */
    public function week($expression): self
    {
        $this->expr->week($expression);

        return $this;
    }

    /**
     * Returns the year portion of a date.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/year/
     * @see Expr::year
     *
     * @param mixed|Expr $expression
     */
    public function year($expression): self
    {
        $this->expr->year($expression);

        return $this;
    }

    /**
     * Transposes an array of input arrays so that the first element of the
     * output array would be an array containing, the first element of the first
     * input array, the first element of the second input array, etc.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/zip/
     * @see Expr::zip
     *
     * @param mixed|Expr      $inputs           An array of expressions that resolve to arrays. The elements of these input arrays combine to form the arrays of the output array.
     * @param bool|null       $useLongestLength A boolean which specifies whether the length of the longest array determines the number of arrays in the output array.
     * @param mixed|Expr|null $defaults         An array of default element values to use if the input arrays have different lengths. You must specify useLongestLength: true along with this field, or else $zip will return an error.
     */
    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): self
    {
        $this->expr->zip($inputs, $useLongestLength, $defaults);

        return $this;
    }
}
