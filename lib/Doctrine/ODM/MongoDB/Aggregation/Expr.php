<?php

namespace Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Expr
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var ClassMetadata
     */
    private $class;

    /**
     * @var array
     */
    private $expr = [];

    /**
     * The current field we are operating on.
     *
     * @var string
     */
    private $currentField;

    /**
     * @var array
     */
    private $switchBranch;

    /**
     * @inheritDoc
     */
    public function __construct(DocumentManager $dm, ClassMetadata $class)
    {
        $this->dm = $dm;
        $this->class = $class;
    }

    /**
     * @param string $method
     * @param array $args
     * @return $this
     */
    public function __call($method, $args)
    {
        $internalMethodName = $method . 'Internal';
        if (! is_callable([$this, $internalMethodName])) {
            throw new \BadMethodCallException('The method ' . $method . ' does not exist.');
        }

        return $this->$internalMethodName(...$args);
    }

    /**
     * Returns the absolute value of a number.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/abs/
     * @param mixed|self $number
     * @return $this
     *
     * @since 1.3
     */
    public function abs($number)
    {
        return $this->operator('$abs', $number);
    }

    /**
     * Adds numbers together or adds numbers and a date. If one of the arguments
     * is a date, $add treats the other arguments as milliseconds to add to the
     * date.
     *
     * The arguments can be any valid expression as long as they resolve to
     * either all numbers or to numbers and a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/add/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self $expression3,... Additional expressions
     * @return $this
     */
    public function add($expression1, $expression2 /* , $expression3, ... */)
    {
        return $this->operator('$add', func_get_args());
    }

    /**
     * Adds one or more $and clauses to the current expression.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/and/
     * @param array|self $expression
     * @return $this
     */
    public function addAnd($expression /*, $expression2, ... */)
    {
        if (! isset($this->expr['$and'])) {
            $this->expr['$and'] = [];
        }

        $this->expr['$and'] = array_merge($this->expr['$and'], array_map([$this, 'ensureArray'], func_get_args()));

        return $this;
    }

    /**
     * Adds one or more $or clause to the current expression.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/or/
     * @param array|self $expression
     * @return $this
     */
    public function addOr($expression /*, $expression2, ... */)
    {
        if (! isset($this->expr['$or'])) {
            $this->expr['$or'] = [];
        }

        $this->expr['$or'] = array_merge($this->expr['$or'], array_map([$this, 'ensureArray'], func_get_args()));

        return $this;
    }

    /**
     * Returns an array of all unique values that results from applying an
     * expression to each document in a group of documents that share the same
     * group by key. Order of the elements in the output array is unspecified.
     *
     * AddToSet is an accumulator operation only available in the group stage.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/addToSet/
     * @param mixed|self $expression
     * @return $this
     */
    public function addToSet($expression)
    {
        return $this->operator('$addToSet', $expression);
    }

    /**
     * Evaluates an array as a set and returns true if no element in the array
     * is false. Otherwise, returns false. An empty array returns true.
     *
     * The expression must resolve to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/allElementsTrue/
     * @param mixed|self $expression
     * @return $this
     */
    public function allElementsTrue($expression)
    {
        return $this->operator('$allElementsTrue', $expression);
    }

    /**
     * Evaluates an array as a set and returns true if any of the elements are
     * true and false otherwise. An empty array returns false.
     *
     * The expression must resolve to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/anyElementTrue/
     * @param array|self $expression
     * @return $this
     */
    public function anyElementTrue($expression)
    {
        return $this->operator('$anyElementTrue', $expression);
    }

    /**
     * Returns the element at the specified array index.
     *
     * The <array> expression can be any valid expression as long as it resolves
     * to an array.
     * The <idx> expression can be any valid expression as long as it resolves
     * to an integer.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/arrayElemAt/
     * @param mixed|self $array
     * @param mixed|self $index
     * @return $this
     *
     * @since 1.3
     */
    public function arrayElemAt($array, $index)
    {
        return $this->operator('$arrayElemAt', [$array, $index]);
    }

    /**
     * Returns the average value of the numeric values that result from applying
     * a specified expression to each document in a group of documents that
     * share the same group by key. Ignores nun-numeric values.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/avg/
     * @param mixed|self $expression
     * @return $this
     */
    public function avg($expression)
    {
        return $this->operator('$avg', $expression);
    }

    /**
     * Adds a case statement for a branch of the $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression that resolves to a boolean. If the result is not a
     * boolean, it is coerced to a boolean value.
     *
     * @param mixed|self $expression
     *
     * @return $this
     *
     * @deprecated Method will be renamed to "case" in next major version
     */
    protected function caseInternal($expression)
    {
        $this->requiresSwitchStatement(static::class . '::case');

        $this->switchBranch = ['case' => $expression];

        return $this;
    }

    /**
     * Returns the smallest integer greater than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/ceil/
     * @param mixed|self $number
     * @return $this
     *
     * @since 1.3
     */
    public function ceil($number)
    {
        return $this->operator('$ceil', $number);
    }

    /**
     * Compares two values and returns:
     * -1 if the first value is less than the second.
     * 1 if the first value is greater than the second.
     * 0 if the two values are equivalent.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/cmp/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function cmp($expression1, $expression2)
    {
        return $this->operator('$cmp', [$expression1, $expression2]);
    }

    /**
     * Concatenates strings and returns the concatenated string.
     *
     * The arguments can be any valid expression as long as they resolve to
     * strings. If the argument resolves to a value of null or refers to a field
     * that is missing, $concat returns null.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/concat/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self $expression3,... Additional expressions
     * @return $this
     */
    public function concat($expression1, $expression2 /* , $expression3, ... */)
    {
        return $this->operator('$concat', func_get_args());
    }

    /**
     * Concatenates arrays to return the concatenated array.
     *
     * The <array> expressions can be any valid expression as long as they
     * resolve to an array.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/concatArrays/
     * @param mixed|self $array1
     * @param mixed|self $array2
     * @param mixed|self $array3, ... Additional expressions
     * @return $this
     *
     * @since 1.3
     */
    public function concatArrays($array1, $array2 /* , $array3, ... */)
    {
        return $this->operator('$concatArrays', func_get_args());
    }

    /**
     * Evaluates a boolean expression to return one of the two specified return
     * expressions.
     *
     * The arguments can be any valid expression.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/cond/
     * @param mixed|self $if
     * @param mixed|self $then
     * @param mixed|self $else
     * @return $this
     */
    public function cond($if, $then, $else)
    {
        return $this->operator('$cond', ['if' => $if, 'then' => $then, 'else' => $else]);
    }

    /**
     * Converts an expression object into an array, recursing into nested items
     *
     * For expression objects, it calls getExpression on the expression object.
     * For arrays, it recursively calls itself for each array item. Other values
     * are returned directly.
     *
     * @param mixed|self $expression
     * @return string|array
     * @internal
     */
    public static function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map(['static', 'convertExpression'], $expression);
        } elseif ($expression instanceof self) {
            return $expression->getExpression();
        }

        return $expression;
    }

    /**
     * Converts a date object to a string according to a user-specified format.
     *
     * The format string can be any string literal, containing 0 or more format
     * specifiers.
     * The date argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/dateToString/
     * @param string $format
     * @param mixed|self $expression
     * @return $this
     */
    public function dateToString($format, $expression)
    {
        return $this->operator('$dateToString', ['format' => $format, 'date' => $expression]);
    }

    /**
     * Returns the day of the month for a date as a number between 1 and 31.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/dayOfMonth/
     * @param mixed|self $expression
     * @return $this
     */
    public function dayOfMonth($expression)
    {
        return $this->operator('$dayOfMonth', $expression);
    }

    /**
     * Returns the day of the week for a date as a number between 1 (Sunday) and
     * 7 (Saturday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/dayOfWeek/
     * @param mixed|self $expression
     * @return $this
     */
    public function dayOfWeek($expression)
    {
        return $this->operator('$dayOfWeek', $expression);
    }

    /**
     * Returns the day of the year for a date as a number between 1 and 366.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/dayOfYear/
     * @param mixed|self $expression
     * @return $this
     */
    public function dayOfYear($expression)
    {
        return $this->operator('$dayOfYear', $expression);
    }

    /**
     * Adds a default statement for the current $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression.
     *
     * Note: if no default is specified and no branch evaluates to true, the
     * $switch operator throws an error.
     *
     * @param mixed|self $expression
     *
     * @return $this
     *
     * @deprecated Method will be renamed to "default" in next major version
     */
    protected function defaultInternal($expression)
    {
        $this->requiresSwitchStatement(static::class . '::default');

        if ($this->currentField) {
            $this->expr[$this->currentField]['$switch']['default'] = $this->ensureArray($expression);
        } else {
            $this->expr['$switch']['default'] = $this->ensureArray($expression);
        }

        return $this;
    }

    /**
     * Divides one number by another and returns the result. The first argument
     * is divided by the second argument.
     *
     * The arguments can be any valid expression as long as the resolve to numbers.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/divide/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function divide($expression1, $expression2)
    {
        return $this->operator('$divide', [$expression1, $expression2]);
    }

    /**
     * Compares two values and returns whether the are equivalent.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/eq/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function eq($expression1, $expression2)
    {
        return $this->operator('$eq', [$expression1, $expression2]);
    }

    /**
     * Raises Euler’s number to the specified exponent and returns the result.
     *
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/exp/
     * @param mixed|self $exponent
     * @return $this
     *
     * @since 1.3
     */
    public function exp($exponent)
    {
        return $this->operator('$exp', $exponent);
    }

    /**
     * Returns a new expression object
     *
     * @return static
     *
     * @since 1.3
     */
    public function expr()
    {
        return new static($this->dm, $this->class);
    }

    /**
     * Allows any expression to be used as a field value.
     *
     * @see http://docs.mongodb.org/manual/meta/aggregation-quick-reference/#aggregation-expressions
     * @param mixed|self $value
     * @return $this
     */
    public function expression($value)
    {
        $this->requiresCurrentField(__METHOD__);
        $this->expr[$this->currentField] = $this->ensureArray($value);

        return $this;
    }

    /**
     * Set the current field for building the expression.
     *
     * @param string $fieldName
     * @return $this
     */
    public function field($fieldName)
    {
        $fieldName = $this->getDocumentPersister()->prepareFieldName($fieldName);
        $this->currentField = (string) $fieldName;

        return $this;
    }

    /**
     * Selects a subset of the array to return based on the specified condition.
     *
     * Returns an array with only those elements that match the condition. The
     * returned elements are in the original order.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/filter/
     * @param mixed|self $input
     * @param mixed|self $as
     * @param mixed|self $cond
     * @return $this
     *
     * @since 1.3
     */
    public function filter($input, $as, $cond)
    {
        return $this->operator('$filter', ['input' => $input, 'as' => $as, 'cond' => $cond]);
    }

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents that share the same group by key. Only
     * meaningful when documents are in a defined order.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/first/
     * @param mixed|self $expression
     * @return $this
     */
    public function first($expression)
    {
        return $this->operator('$first', $expression);
    }

    /**
     * Returns the largest integer less than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/floor/
     * @param mixed|self $number
     * @return $this
     *
     * @since 1.3
     */
    public function floor($number)
    {
        return $this->operator('$floor', $number);
    }

    /**
     * @return array
     */
    public function getExpression()
    {
        return $this->expr;
    }

    /**
     * Compares two values and returns:
     * true when the first value is greater than the second value.
     * false when the first value is less than or equivalent to the second
     * value.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/gt/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function gt($expression1, $expression2)
    {
        return $this->operator('$gt', [$expression1, $expression2]);
    }

    /**
     * Compares two values and returns:
     * true when the first value is greater than or equivalent to the second
     * value.
     * false when the first value is less than the second value.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/gte/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function gte($expression1, $expression2)
    {
        return $this->operator('$gte', [$expression1, $expression2]);
    }

    /**
     * Returns the hour portion of a date as a number between 0 and 23.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/hour/
     * @param mixed|self $expression
     * @return $this
     */
    public function hour($expression)
    {
        return $this->operator('$hour', $expression);
    }

    /**
     * Evaluates an expression and returns the value of the expression if the
     * expression evaluates to a non-null value. If the expression evaluates to
     * a null value, including instances of undefined values or missing fields,
     * returns the value of the replacement expression.
     *
     * The arguments can be any valid expression.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/ifNull/
     * @param mixed|self $expression
     * @param mixed|self $replacementExpression
     * @return $this
     */
    public function ifNull($expression, $replacementExpression)
    {
        return $this->operator('$ifNull', [$expression, $replacementExpression]);
    }

    /**
     * Returns a boolean indicating whether a specified value is in an array.
     *
     * Unlike the $in query operator, the aggregation $in operator does not
     * support matching by regular expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/in/
     * @since 1.5
     * @param mixed|self $expression
     * @param mixed|self $arrayExpression
     * @return $this
     */
    public function in($expression, $arrayExpression)
    {
        return $this->operator('$in', [$expression, $arrayExpression]);
    }

    /**
     * Searches an array for an occurence of a specified value and returns the
     * array index (zero-based) of the first occurence. If the value is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfArray/
     * @since 1.5
     * @param mixed|self $arrayExpression Can be any valid expression as long as it resolves to an array.
     * @param mixed|self $searchExpression Can be any valid expression.
     * @param mixed|self $start Optional. An integer, or a number that can be represented as integers (such as 2.0), that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param mixed|self $end An integer, or a number that can be represented as integers (such as 2.0), that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @return $this
     */
    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null)
    {
        $args = [$arrayExpression, $searchExpression];
        if ($start !== null) {
            $args[] = $start;

            if ($end !== null) {
                $args[] = $end;
            }
        }

        return $this->operator('$indexOfArray', $args);
    }

    /**
     * Searches a string for an occurence of a substring and returns the UTF-8
     * byte index (zero-based) of the first occurence. If the substring is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfBytes/
     * @since 1.5
     * @param mixed|self $stringExpression Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $substringExpression Can be any valid expression as long as it resolves to a string.
     * @param int|null $start An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param int|null $end An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     *
     * @return $this
     */
    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null)
    {
        $args = [$stringExpression, $substringExpression];
        if ($start !== null) {
            $args[] = $start;

            if ($end !== null) {
                $args[] = $end;
            }
        }

        return $this->operator('$indexOfBytes', $args);
    }

    /**
     * Searches a string for an occurence of a substring and returns the UTF-8
     * code point index (zero-based) of the first occurence. If the substring is
     * not found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfCP/
     * @since 1.5
     * @param mixed|self $stringExpression Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $substringExpression Can be any valid expression as long as it resolves to a string.
     * @param int|null $start An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param int|null $end An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     *
     * @return $this
     */
    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null)
    {
        $args = [$stringExpression, $substringExpression];
        if ($start !== null) {
            $args[] = $start;

            if ($end !== null) {
                $args[] = $end;
            }
        }

        return $this->operator('$indexOfCP', $args);
    }

    /**
     * Determines if the operand is an array. Returns a boolean.
     *
     * The <expression> can be any valid expression.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/isArray/
     * @param mixed|self $expression
     * @return $this
     *
     * @since 1.3
     */
    public function isArray($expression)
    {
        return $this->operator('$isArray', $expression);
    }

    /**
     * Returns the weekday number in ISO 8601 format, ranging from 1 (for Monday)
     * to 7 (for Sunday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/isoDayOfWeek/
     * @since 1.5
     * @param mixed|self $expression
     * @return $this
     */
    public function isoDayOfWeek($expression)
    {
        return $this->operator('$isoDayOfWeek', $expression);
    }

    /**
     * Returns the week number in ISO 8601 format, ranging from 1 to 53.
     *
     * Week numbers start at 1 with the week (Monday through Sunday) that
     * contains the year’s first Thursday.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/isoWeek/
     * @since 1.5
     * @param mixed|self $expression
     * @return $this
     */
    public function isoWeek($expression)
    {
        return $this->operator('$isoWeek', $expression);
    }

    /**
     * Returns the year number in ISO 8601 format.
     *
     * The year starts with the Monday of week 1 (ISO 8601) and ends with the
     * Sunday of the last week (ISO 8601).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/isoWeek/
     * @since 1.5
     * @param mixed|self $expression
     * @return $this
     */
    public function isoWeekYear($expression)
    {
        return $this->operator('$isoWeekYear', $expression);
    }

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents that share the same group by a field.
     * Only meaningful when documents are in a defined order.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/last/
     * @param mixed|self $expression
     * @return $this
     */
    public function last($expression)
    {
        return $this->operator('$last', $expression);
    }

    /**
     * Binds variables for use in the specified expression, and returns the
     * result of the expression.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/let/
     * @param mixed|self $vars Assignment block for the variables accessible in the in expression. To assign a variable, specify a string for the variable name and assign a valid expression for the value.
     * @param mixed|self $in   The expression to evaluate.
     * @return $this
     */
    public function let($vars, $in)
    {
        return $this->operator('$let', ['vars' => $vars, 'in' => $in]);
    }

    /**
     * Returns a value without parsing. Use for values that the aggregation
     * pipeline may interpret as an expression.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/literal/
     * @param mixed|self $value
     * @return $this
     */
    public function literal($value)
    {
        return $this->operator('$literal', $value);
    }

    /**
     * Calculates the natural logarithm ln (i.e loge) of a number and returns
     * the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/log/
     * @param mixed|self $number
     * @return $this
     *
     * @since 1.3
     */
    public function ln($number)
    {
        return $this->operator('$ln', $number);
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
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/log/
     * @param mixed|self $number
     * @param mixed|self $base
     * @return $this
     *
     * @since 1.3
     */
    public function log($number, $base)
    {
        return $this->operator('$log', [$number, $base]);
    }

    /**
     * Calculates the log base 10 of a number and returns the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/log10/
     * @param mixed|self $number
     * @return $this
     *
     * @since 1.3
     */
    public function log10($number)
    {
        return $this->operator('$log10', $number);
    }

    /**
     * Compares two values and returns:
     * true when the first value is less than the second value.
     * false when the first value is greater than or equivalent to the second
     * value.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/lt/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function lt($expression1, $expression2)
    {
        return $this->operator('$lt', [$expression1, $expression2]);
    }

    /**
     * Compares two values and returns:
     * true when the first value is less than or equivalent to the second value.
     * false when the first value is greater than the second value.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/lte/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function lte($expression1, $expression2)
    {
        return $this->operator('$lte', [$expression1, $expression2]);
    }

    /**
     * Applies an expression to each item in an array and returns an array with
     * the applied results.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/map/
     * @param mixed|self $input An expression that resolves to an array.
     * @param string $as        The variable name for the items in the input array. The in expression accesses each item in the input array by this variable.
     * @param mixed|self $in    The expression to apply to each item in the input array. The expression accesses the item by its variable name.
     * @return $this
     */
    public function map($input, $as, $in)
    {
        return $this->operator('$map', ['input' => $input, 'as' => $as, 'in' => $in]);
    }

    /**
     * Returns the highest value that results from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/max/
     * @param mixed|self $expression
     * @return $this
     */
    public function max($expression)
    {
        return $this->operator('$max', $expression);
    }

    /**
     * Returns the metadata associated with a document in a pipeline operations.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/meta/
     * @param $metaDataKeyword
     * @return $this
     */
    public function meta($metaDataKeyword)
    {
        return $this->operator('$meta', $metaDataKeyword);
    }

    /**
     * Returns the millisecond portion of a date as an integer between 0 and 999.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/millisecond/
     * @param mixed|self $expression
     * @return $this
     */
    public function millisecond($expression)
    {
        return $this->operator('$millisecond', $expression);
    }

    /**
     * Returns the lowest value that results from applying an expression to each
     * document in a group of documents that share the same group by key.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/min/
     * @param mixed|self $expression
     * @return $this
     */
    public function min($expression)
    {
        return $this->operator('$min', $expression);
    }

    /**
     * Returns the minute portion of a date as a number between 0 and 59.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/minute/
     * @param mixed|self $expression
     * @return $this
     */
    public function minute($expression)
    {
        return $this->operator('$minute', $expression);
    }

    /**
     * Divides one number by another and returns the remainder. The first
     * argument is divided by the second argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/mod/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function mod($expression1, $expression2)
    {
        return $this->operator('$mod', [$expression1, $expression2]);
    }

    /**
     * Returns the month of a date as a number between 1 and 12.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/month/
     * @param mixed|self $expression
     * @return $this
     */
    public function month($expression)
    {
        return $this->operator('$month', $expression);
    }

    /**
     * Multiplies numbers together and returns the result.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/multiply/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self $expression3,... Additional expressions
     * @return $this
     */
    public function multiply($expression1, $expression2 /* , $expression3, ... */)
    {
        return $this->operator('$multiply', func_get_args());
    }

    /**
     * Compares two values and returns:
     * true when the values are not equivalent.
     * false when the values are equivalent.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/ne/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function ne($expression1, $expression2)
    {
        return $this->operator('$ne', [$expression1, $expression2]);
    }

    /**
     * Evaluates a boolean and returns the opposite boolean value.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/not/
     * @param mixed|self $expression
     * @return $this
     */
    public function not($expression)
    {
        return $this->operator('$not', $expression);
    }

    /**
     * Raises a number to the specified exponent and returns the result.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/pow/
     * @param mixed|self $number
     * @param mixed|self $exponent
     * @return $this
     *
     * @since 1.3
     */
    public function pow($number, $exponent)
    {
        return $this->operator('$pow', [$number, $exponent]);
    }

    /**
     * Returns an array of all values that result from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/push/
     * @param mixed|self $expression
     * @return $this
     */
    public function push($expression)
    {
        return $this->operator('$push', $expression);
    }

    /**
     * Returns an array whose elements are a generated sequence of numbers.
     *
     * $range generates the sequence from the specified starting number by successively incrementing the starting number by the specified step value up to but not including the end point.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/range/
     * @since 1.5
     * @param mixed|self $start An integer that specifies the start of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|self $end An integer that specifies the exclusive upper limit of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|self $step Optional. An integer that specifies the increment value. Can be any valid expression that resolves to a non-zero integer. Defaults to 1.
     * @return $this
     */
    public function range($start, $end, $step = 1)
    {
        return $this->operator('$range', [$start, $end, $step]);
    }

    /**
     * Applies an expression to each element in an array and combines them into
     * a single value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reduce/
     * @since 1.5
     * @param mixed|self $input Can be any valid expression that resolves to an array.
     * @param mixed|self $initialValue The initial cumulative value set before in is applied to the first element of the input array.
     * @param mixed|self $in A valid expression that $reduce applies to each element in the input array in left-to-right order. Wrap the input value with $reverseArray to yield the equivalent of applying the combining expression from right-to-left.
     * @return $this
     */
    public function reduce($input, $initialValue, $in)
    {
        return $this->operator('$reduce', ['input' => $input, 'initialValue' => $initialValue, 'in' => $in]);
    }

    /**
     * Accepts an array expression as an argument and returns an array with the
     * elements in reverse order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reverseArray/
     * @since 1.5
     * @param mixed|self $expression
     * @return $this
     */
    public function reverseArray($expression)
    {
        return $this->operator('$reverseArray', $expression);
    }

    /**
     * Returns the second portion of a date as a number between 0 and 59, but
     * can be 60 to account for leap seconds.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/second/
     * @param mixed|self $expression
     * @return $this
     */
    public function second($expression)
    {
        return $this->operator('$second', $expression);
    }

    /**
     * Takes two sets and returns an array containing the elements that only
     * exist in the first set.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/setDifference/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function setDifference($expression1, $expression2)
    {
        return $this->operator('$setDifference', [$expression1, $expression2]);
    }

    /**
     * Compares two or more arrays and returns true if they have the same
     * distinct elements and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/setEquals/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self $expression3,...   Additional sets
     * @return $this
     */
    public function setEquals($expression1, $expression2 /* , $expression3, ... */)
    {
        return $this->operator('$setEquals', func_get_args());
    }

    /**
     * Takes two or more arrays and returns an array that contains the elements
     * that appear in every input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/setIntersection/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self $expression3,...   Additional sets
     * @return $this
     */
    public function setIntersection($expression1, $expression2 /* , $expression3, ... */)
    {
        return $this->operator('$setIntersection', func_get_args());
    }

    /**
     * Takes two arrays and returns true when the first array is a subset of the
     * second, including when the first array equals the second array, and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/setIsSubset/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function setIsSubset($expression1, $expression2)
    {
        return $this->operator('$setIsSubset', [$expression1, $expression2]);
    }

    /**
     * Takes two or more arrays and returns an array containing the elements
     * that appear in any input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/setUnion/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self $expression3,...   Additional sets
     * @return $this
     */
    public function setUnion($expression1, $expression2 /* , $expression3, ... */)
    {
        return $this->operator('$setUnion', func_get_args());
    }

    /**
     * Counts and returns the total the number of items in an array.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/size/
     * @param mixed|self $expression
     * @return $this
     */
    public function size($expression)
    {
        return $this->operator('$size', $expression);
    }

    /**
     * Returns a subset of an array.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/slice/
     * @param mixed|self $array
     * @param mixed|self $n
     * @param mixed|self|null $position
     * @return $this
     *
     * @since 1.3
     */
    public function slice($array, $n, $position = null)
    {
        if ($position === null) {
            return $this->operator('$slice', [$array, $n]);
        }

        return $this->operator('$slice', [$array, $position, $n]);
    }

    /**
     * Divides a string into an array of substrings based on a delimiter.
     *
     * $split removes the delimiter and returns the resulting substrings as
     * elements of an array. If the delimiter is not found in the string, $split
     * returns the original string as the only element of an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/split/
     * @since 1.5
     * @param mixed|self $string The string to be split. Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $delimiter The delimiter to use when splitting the string expression. Can be any valid expression as long as it resolves to a string.
     *
     * @return $this
     */
    public function split($string, $delimiter)
    {
        return $this->operator('$split', [$string, $delimiter]);
    }

    /**
     * Calculates the square root of a positive number and returns the result as
     * a double.
     *
     * The argument can be any valid expression as long as it resolves to a
     * non-negative number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/sqrt/
     * @param mixed|self $expression
     * @return $this
     */
    public function sqrt($expression)
    {
        return $this->operator('$sqrt', $expression);
    }

    /**
     * Calculates the population standard deviation of the input values.
     *
     * The arguments can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/stdDevPop/
     * @param mixed|self $expression1
     * @param mixed|self $expression2,... Additional samples
     * @return $this
     *
     * @since 1.3
     */
    public function stdDevPop($expression1 /* , $expression2, ... */)
    {
        $expression = (func_num_args() === 1) ? $expression1 : func_get_args();

        return $this->operator('$stdDevPop', $expression);
    }

    /**
     * Calculates the sample standard deviation of the input values.
     *
     * The arguments can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/stdDevSamp/
     * @param mixed|self $expression1
     * @param mixed|self $expression2,... Additional samples
     * @return $this
     *
     * @since 1.3
     */
    public function stdDevSamp($expression1 /* , $expression2, ... */)
    {
        $expression = (func_num_args() === 1) ? $expression1 : func_get_args();

        return $this->operator('$stdDevSamp', $expression);
    }

    /**
     * Performs case-insensitive comparison of two strings. Returns
     * 1 if first string is “greater than” the second string.
     * 0 if the two strings are equal.
     * -1 if the first string is “less than” the second string.
     *
     * The arguments can be any valid expression as long as they resolve to strings.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/strcasecmp/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function strcasecmp($expression1, $expression2)
    {
        return $this->operator('$strcasecmp', [$expression1, $expression2]);
    }

    /**
     * Returns the number of UTF-8 encoded bytes in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenBytes/
     * @since 1.5
     * @param mixed|self $string
     *
     * @return $this
     */
    public function strLenBytes($string)
    {
        return $this->operator('$strLenBytes', $string);
    }

    /**
     * Returns the number of UTF-8 code points in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenCP/
     * @since 1.5
     * @param mixed|self $string
     *
     * @return $this
     */
    public function strLenCP($string)
    {
        return $this->operator('$strLenCP', $string);
    }

    /**
     * Returns a substring of a string, starting at a specified index position
     * and including the specified number of characters. The index is zero-based.
     *
     * The arguments can be any valid expression as long as long as the first argument resolves to a string, and the second and third arguments resolve to integers.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/substr/
     * @param mixed|self $string
     * @param mixed|self $start
     * @param mixed|self $length
     * @return $this
     */
    public function substr($string, $start, $length)
    {
        return $this->operator('$substr', [$string, $start, $length]);
    }

    /**
     * Returns the substring of a string.
     *
     * The substring starts with the character at the specified UTF-8 byte index
     * (zero-based) in the string and continues for the number of bytes
     * specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substrBytes/
     * @since 1.5
     * @param mixed|self $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $start Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|self $count Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     *
     * @return $this
     */
    public function substrBytes($string, $start, $count)
    {
        return $this->operator('$substrBytes', [$string, $start, $count]);
    }

    /**
     * Returns the substring of a string.
     *
     * The substring starts with the character at the specified UTF-8 code point
     * (CP) index (zero-based) in the string for the number of code points
     * specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substrBytes/
     * @since 1.5
     * @param mixed|self $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $start Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|self $count Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     *
     * @return $this
     */
    public function substrCP($string, $start, $count)
    {
        return $this->operator('$substrCP', [$string, $start, $count]);
    }

    /**
     * Subtracts two numbers to return the difference. The second argument is
     * subtracted from the first argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers and/or dates.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/subtract/
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @return $this
     */
    public function subtract($expression1, $expression2)
    {
        return $this->operator('$subtract', [$expression1, $expression2]);
    }

    /**
     * Calculates and returns the sum of all the numeric values that result from
     * applying a specified expression to each document in a group of documents
     * that share the same group by key. Ignores nun-numeric values.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/sum/
     * @param mixed|self $expression
     * @return $this
     */
    public function sum($expression)
    {
        return $this->operator('$sum', $expression);
    }

    /**
     * Converts a string to lowercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/toLower/
     * @param mixed|self $expression
     * @return $this
     */
    public function toLower($expression)
    {
        return $this->operator('$toLower', $expression);
    }

    /**
     * Converts a string to uppercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/toUpper/
     * @param mixed|self $expression
     * @return $this
     */
    public function toUpper($expression)
    {
        return $this->operator('$toUpper', $expression);
    }

    /**
     * Truncates a number to its integer.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.org/manual/reference/operator/aggregation/trunc/
     * @param mixed|self $number
     * @return $this
     *
     * @since 1.3
     */
    public function trunc($number)
    {
        return $this->operator('$trunc', $number);
    }

    /**
     * Returns a string that specifies the BSON type of the argument.
     *
     * The argument can be any valid expression.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/type/
     * @since 1.5
     * @param mixed|self $expression
     *
     * @return $this
     */
    public function type($expression)
    {
        return $this->operator('$type', $expression);
    }

    /**
     * Returns the week of the year for a date as a number between 0 and 53.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/week/
     * @param mixed|self $expression
     * @return $this
     */
    public function week($expression)
    {
        return $this->operator('$week', $expression);
    }

    /**
     * Returns the year portion of a date.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/aggregation/year/
     * @param mixed|self $expression
     * @return $this
     */
    public function year($expression)
    {
        return $this->operator('$year', $expression);
    }

    /**
     * Transposes an array of input arrays so that the first element of the
     * output array would be an array containing, the first element of the first
     * input array, the first element of the second input array, etc.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/zip/
     * @since 1.5
     * @param mixed|self $inputs An array of expressions that resolve to arrays. The elements of these input arrays combine to form the arrays of the output array.
     * @param bool|null $useLongestLength A boolean which specifies whether the length of the longest array determines the number of arrays in the output array.
     * @param mixed|self|null $defaults An array of default element values to use if the input arrays have different lengths. You must specify useLongestLength: true along with this field, or else $zip will return an error.
     * @return $this
     */
    public function zip($inputs, $useLongestLength = null, $defaults = null)
    {
        $args = ['inputs' => $inputs];
        if ($useLongestLength !== null) {
            $args['useLongestLength'] = $useLongestLength;
        }
        if ($defaults !== null) {
            $args['defaults'] = $defaults;
        }

        return $this->operator('$zip', $args);
    }


    /**
     * @param mixed|self $expression
     * @return mixed
     */
    private function ensureArray($expression)
    {
        if (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        } elseif (is_array($expression)) {
            return array_map([$this, 'ensureArray'], $expression);
        } elseif ($expression instanceof self) {
            return $expression->getExpression();
        }

        // Convert PHP types to MongoDB types for everything else
        return Type::convertPHPToDatabaseValue($expression);
    }

    /**
     * @return \Doctrine\ODM\MongoDB\Persisters\DocumentPersister
     */
    private function getDocumentPersister()
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }

    /**
     * Defines an operator and value on the expression.
     *
     * If there is a current field, the operator will be set on it; otherwise,
     * the operator is set at the top level of the query.
     *
     * @param string $operator
     * @param array|self[]|self $expression
     * @return $this
     */
    private function operator($operator, $expression)
    {
        if ($this->currentField) {
            $this->expr[$this->currentField][$operator] = $this->ensureArray($expression);
        } else {
            $this->expr[$operator] = $this->ensureArray($expression);
        }

        return $this;
    }

    /**
     * Ensure that a current field has been set.
     *
     * @param string $method
     *
     * @throws \LogicException if a current field has not been set
     */
    private function requiresCurrentField($method = null)
    {
        if ( ! $this->currentField) {
            throw new \LogicException(($method ?: 'This method') . ' requires you set a current field using field().');
        }
    }

    /**
     * @param string $method
     *
     * @throws \BadMethodCallException if there is no current switch operator
     */
    private function requiresSwitchStatement($method = null)
    {
        $message = ($method ?: 'This method') . ' requires a valid switch statement (call switch() first).';

        if ($this->currentField) {
            if (! isset($this->expr[$this->currentField]['$switch'])) {
                throw new \BadMethodCallException($message);
            }
        } elseif (! isset($this->expr['$switch'])) {
            throw new \BadMethodCallException($message);
        }
    }

    /**
     * Evaluates a series of case expressions. When it finds an expression which
     * evaluates to true, $switch executes a specified expression and breaks out
     * of the control flow.
     *
     * To add statements, use the {@link case()}, {@link then()} and
     * {@link default()} methods.
     *
     * @return $this
     *
     * @deprecated Method will be renamed to "switch" in next major version
     */
    private function switchInternal()
    {
        $this->operator('$switch', []);

        return $this;
    }

    /**
     * Adds a case statement for the current branch of the $switch operator.
     *
     * Requires {@link case()} to be called first. The argument can be any valid
     * expression.
     *
     * @param mixed|self $expression
     *
     * @return $this
     *
     * @deprecated Method will be renamed to "then" in next major version
     */
    private function thenInternal($expression)
    {
        if (! is_array($this->switchBranch)) {
            throw new \BadMethodCallException(static::class . '::then requires a valid case statement (call case() first).');
        }

        $this->switchBranch['then'] = $expression;

        if ($this->currentField) {
            $this->expr[$this->currentField]['$switch']['branches'][] = $this->ensureArray($this->switchBranch);
        } else {
            $this->expr['$switch']['branches'][] = $this->ensureArray($this->switchBranch);
        }

        $this->switchBranch = null;

        return $this;
    }

}
