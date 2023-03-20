<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Operator\AccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArithmeticOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArrayOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\BooleanOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ConditionalOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\DataSizeOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\DateOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\MiscOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ObjectOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\SetOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\StringOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\TimestampOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\TrigonometryOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\TypeOperators;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

use function func_get_args;

/**
 * Fluent interface for adding operators to aggregation stages.
 *
 * @internal
 */
abstract class Operator extends Stage implements
    AccumulatorOperators,
    ArithmeticOperators,
    ArrayOperators,
    BooleanOperators,
    ConditionalOperators,
    DataSizeOperators,
    DateOperators,
    MiscOperators,
    ObjectOperators,
    SetOperators,
    StringOperators,
    TimestampOperators,
    TrigonometryOperators,
    TypeOperators
{
    /** @var Expr */
    protected $expr;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->expr = $builder->expr();
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
     *
     * @return static
     */
    public function abs($number): self
    {
        $this->expr->abs($number);

        return $this;
    }

    /**
     * Returns the inverse cosine (arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acos/
     *
     * @param mixed|Expr $expression
     */
    public function acos($expression): self
    {
        $this->expr->acos($expression);

        return $this;
    }

    /**
     * Returns the inverse hyperbolic cosine (hyperbolic arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acosh/
     *
     * @param mixed|Expr $expression
     */
    public function acosh($expression): self
    {
        $this->expr->acosh($expression);

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
     *
     * @return static
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
     * @param mixed[]|Expr $expression
     * @param mixed[]|Expr ...$expressions
     *
     * @return static
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
     * @param mixed[]|Expr $expression
     * @param mixed[]|Expr ...$expressions
     *
     * @return static
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
     *
     * @return static
     */
    public function allElementsTrue($expression): self
    {
        $this->expr->allElementsTrue($expression);

        return $this;
    }

    /**
     * Adds one or more $and clauses to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/and/
     * @see Expr::and
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function and($expression, ...$expressions): self
    {
        $this->expr->and($expression, ...$expressions);

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
     * @param mixed[]|Expr $expression
     *
     * @return static
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
     *
     * @return static
     */
    public function arrayElemAt($array, $index): self
    {
        $this->expr->arrayElemAt($array, $index);

        return $this;
    }

    /**
     * Converts an array into a single document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayToObject/
     *
     * @param mixed|Expr $array
     */
    public function arrayToObject($array): self
    {
        $this->expr->arrayToObject($array);

        return $this;
    }

    /**
     * Returns the inverse tangent (arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan/
     *
     * @param mixed|Expr $expression
     */
    public function atan($expression): self
    {
        $this->expr->atan($expression);

        return $this;
    }

    /**
     * Returns the inverse sin (arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asin/
     *
     * @param mixed|Expr $expression
     */
    public function asin($expression): self
    {
        $this->expr->asin($expression);

        return $this;
    }

    /**
     * Returns the inverse hyperbolic sine (hyperbolic arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asinh/
     *
     * @param mixed|Expr $expression
     */
    public function asinh($expression): self
    {
        $this->expr->asinh($expression);

        return $this;
    }

    /**
     * Returns the inverse tangent (arc tangent) of y / x in radians, where y and x are the first and second values passed to the expression respectively.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan2/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function atan2($expression1, $expression2): self
    {
        $this->expr->atan2($expression1, $expression2);

        return $this;
    }

    /**
     * Returns the inverse hyperbolic tangent (hyperbolic arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atanh/
     *
     * @param mixed|Expr $expression
     */
    public function atanh($expression): self
    {
        $this->expr->atanh($expression);

        return $this;
    }

    /**
     * Returns the average value of numeric values. Ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function avg($expression, ...$expressions): self
    {
        $this->expr->avg(...func_get_args());

        return $this;
    }

    /**
     * Returns the size of a given string or binary data value's content in bytes.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/binarySize/
     * @see Expr::binarySize
     *
     * @param mixed|Expr $expression
     */
    public function binarySize($expression): self
    {
        $this->expr->binarySize($expression);

        return $this;
    }

    /**
     * Returns the size in bytes of a given document (i.e. bsontype Object) when encoded as BSON.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bsonSize/
     * @see Expr::bsonSize
     *
     * @param mixed|Expr $expression
     */
    public function bsonSize($expression): self
    {
        $this->expr->bsonSize($expression);

        return $this;
    }

    /**
     * Adds a case statement for a branch of the $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression that resolves to a boolean. If the result is not a
     * boolean, it is coerced to a boolean value.
     *
     * @param mixed|Expr $expression
     */
    public function case($expression): self
    {
        $this->expr->case($expression);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function cond($if, $then, $else): self
    {
        $this->expr->cond($if, $then, $else);

        return $this;
    }

    /**
     * Converts a value to a specified type.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/convert/
     *
     * @param mixed|Expr      $input
     * @param mixed|Expr      $to
     * @param mixed|Expr|null $onError
     * @param mixed|Expr|null $onNull
     */
    public function convert($input, $to, $onError = null, $onNull = null): self
    {
        $this->expr->convert($input, $to, $onError, $onNull);

        return $this;
    }

    /**
     * Returns the cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cos/
     *
     * @param mixed|Expr $expression
     */
    public function cos($expression): self
    {
        $this->expr->cos($expression);

        return $this;
    }

    /**
     * Returns the hyperbolic cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cosh/
     *
     * @param mixed|Expr $expression
     */
    public function cosh($expression): self
    {
        $this->expr->cosh($expression);

        return $this;
    }

    /**
     * Increments a Date object by a specified number of time units
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateAdd/
     * @see Expr::dateAdd
     *
     * @param mixed|Expr $startDate
     * @param mixed|Expr $unit
     * @param mixed|Expr $amount
     * @param mixed|Expr $timezone
     */
    public function dateAdd($startDate, $unit, $amount, $timezone = null): self
    {
        $this->expr->dateAdd($startDate, $unit, $amount, $timezone);

        return $this;
    }

    /**
     * Returns the difference between two dates
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateDiff/
     * @see Expr::dateDiff
     *
     * @param mixed|Expr $startDate
     * @param mixed|Expr $endDate
     * @param mixed|Expr $unit
     * @param mixed|Expr $timezone
     * @param mixed|Expr $startOfWeek
     */
    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): self
    {
        $this->expr->dateDiff($startDate, $endDate, $unit, $timezone, $startOfWeek);

        return $this;
    }

    /**
     * Constructs and returns a Date object given the date's constituent properties
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromParts/
     * @see Expr::dateFromParts
     *
     * @param mixed|Expr $year
     * @param mixed|Expr $isoWeekYear
     * @param mixed|Expr $month
     * @param mixed|Expr $isoWeek
     * @param mixed|Expr $day
     * @param mixed|Expr $isoDayOfWeek
     * @param mixed|Expr $hour
     * @param mixed|Expr $minute
     * @param mixed|Expr $second
     * @param mixed|Expr $millisecond
     * @param mixed|Expr $timezone
     */
    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): self
    {
        $this->expr->dateFromParts($year, $isoWeekYear, $month, $isoWeek, $day, $isoDayOfWeek, $hour, $minute, $second, $millisecond, $timezone);

        return $this;
    }

    /**
     * Converts a date/time string to a date object.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromString/
     * @see Expr::dateFromString
     *
     * @param mixed|Expr $dateString
     * @param mixed|Expr $format
     * @param mixed|Expr $timezone
     * @param mixed|Expr $onError
     * @param mixed|Expr $onNull
     *
     * @return static
     */
    public function dateFromString($dateString, $format = null, $timezone = null, $onError = null, $onNull = null): self
    {
        $this->expr->dateFromString($dateString, $format, $timezone, $onError, $onNull);

        return $this;
    }

    /**
     * Decrements a Date object by a specified number of time units
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateSubtract/
     * @see Expr::dateSubtract
     *
     * @param mixed|Expr $startDate
     * @param mixed|Expr $unit
     * @param mixed|Expr $amount
     * @param mixed|Expr $timezone
     */
    public function dateSubtract($startDate, $unit, $amount, $timezone = null): self
    {
        $this->expr->dateSubtract($startDate, $unit, $amount, $timezone);

        return $this;
    }

    /**
     * Returns a document that contains the constituent parts of a given BSON Date value as individual properties. The properties returned are year, month, day, hour, minute, second and millisecond.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToParts/
     * @see Expr::dateToParts
     *
     * @param mixed|Expr $date
     * @param mixed|Expr $timezone
     * @param mixed|Expr $iso8601
     */
    public function dateToParts($date, $timezone = null, $iso8601 = null): self
    {
        $this->expr->dateToParts($date, $timezone, $iso8601);

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
     * @param mixed|Expr $expression
     * @param mixed|Expr $timezone
     * @param mixed|Expr $onNull
     *
     * @return static
     */
    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): self
    {
        $this->expr->dateToString($format, $expression, $timezone, $onNull);

        return $this;
    }

    /**
     * Truncates a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateTrunc/
     * @see Expr::dateTrunc
     *
     * @param mixed|Expr $date
     * @param mixed|Expr $unit
     * @param mixed|Expr $binSize
     * @param mixed|Expr $timezone
     * @param mixed|Expr $startOfWeek
     */
    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): self
    {
        $this->expr->dateTrunc($date, $unit, $binSize, $timezone, $startOfWeek);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function dayOfYear($expression): self
    {
        $this->expr->dayOfYear($expression);

        return $this;
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
     * @param mixed|Expr $expression
     */
    public function default($expression): self
    {
        $this->expr->default($expression);

        return $this;
    }

    /**
     * Converts a value from degrees to radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/degreesToRadians/
     *
     * @param mixed|Expr $expression
     */
    public function degreesToRadians($expression): self
    {
        $this->expr->degreesToRadians($expression);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function filter($input, $as, $cond): self
    {
        $this->expr->filter($input, $as, $cond);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents that share the same group by key. Only
     * meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/first/
     *
     * @param mixed|Expr $expression
     */
    public function first($expression): self
    {
        $this->expr->first($expression);

        return $this;
    }

    /**
     * Returns a specified number of elements from the beginning of an array. Distinct from the $firstN accumulator.
     *
     * @see https://www.mongodb.com/docs/manual/reference/operator/aggregation/firstN-array-element/
     * @see Expr::firstN
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function firstN($expression, $n): self
    {
        $this->expr->firstN($expression, $n);

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
     *
     * @return static
     */
    public function floor($number): self
    {
        $this->expr->floor($number);

        return $this;
    }

    /**
     * Returns the value of a specified field from a document. If you don't specify an object, $getField returns the value of the field from $$CURRENT.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/getField/
     * @see Expr::getField
     *
     * @param mixed|Expr $field
     * @param mixed|Expr $input
     */
    public function getField($field, $input = null): self
    {
        $this->expr->getField($field, $input);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function isArray($expression): self
    {
        $this->expr->isArray($expression);

        return $this;
    }

    /**
     * Returns boolean true if the specified expression resolves to an integer, decimal, double, or long.
     * Returns boolean false if the expression resolves to any other BSON type, null, or a missing field.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isNumber/
     *
     * @param mixed|Expr $expression
     */
    public function isNumber($expression): self
    {
        $this->expr->isNumber($expression);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function isoWeekYear($expression): self
    {
        $this->expr->isoWeekYear($expression);

        return $this;
    }

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents that share the same group by a field.
     * Only meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     *
     * @param mixed|Expr $expression
     */
    public function last($expression): self
    {
        $this->expr->last($expression);

        return $this;
    }

    /**
     * Returns a specified number of elements from the end of an array. Distinct from the $lastN accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lastN-array-element/
     * @see Expr::lastN
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function lastN($expression, $n): self
    {
        $this->expr->lastN($expression, $n);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function lte($expression1, $expression2): self
    {
        $this->expr->lte($expression1, $expression2);

        return $this;
    }

    /**
     * Removes whitespace characters, including null, or the specified characters from
     * the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ltrim/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $chars
     */
    public function ltrim($input, $chars = null): self
    {
        $this->expr->ltrim($input, $chars);

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
     *
     * @return static
     */
    public function map($input, $as, $in): self
    {
        $this->expr->map($input, $as, $in);

        return $this;
    }

    /**
     * Returns the maximum value of numeric values.
     *
     * $max compares both value and type, using the BSON comparison order for
     * values of different types.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     * @see Expr::max
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function max($expression, ...$expressions): self
    {
        $this->expr->max($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the n largest values in an array. Distinct from the $maxN accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/maxN/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function maxN($expression, $n): self
    {
        $this->expr->maxN($expression, $n);

        return $this;
    }

    /**
     * Combines multiple documents into a single document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mergeObjects/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function mergeObjects($expression, ...$expressions): self
    {
        $this->expr->mergeObjects($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the metadata associated with a document in a pipeline operations.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/meta/
     * @see Expr::meta
     *
     * @param mixed|Expr $metaDataKeyword
     *
     * @return static
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
     *
     * @return static
     */
    public function millisecond($expression): self
    {
        $this->expr->millisecond($expression);

        return $this;
    }

    /**
     * Returns the minimum value of numeric values.
     *
     * $min compares both value and type, using the BSON comparison order for
     * values of different types.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     * @see Expr::min
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function min($expression, ...$expressions): self
    {
        $this->expr->min($expression, ...$expressions);

        return $this;
    }

    /**
     * Returns the n smallest values in an array. Distinct from the $minN accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minN/
     * @see Expr::minN
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $n
     */
    public function minN($expression, $n): self
    {
        $this->expr->minN($expression, $n);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function not($expression): self
    {
        $this->expr->not($expression);

        return $this;
    }

    /**
     * Converts a document to an array. The return array contains an element for each field/value pair
     * in the original document. Each element in the return array is a document that contains
     * two fields k and v:.
     *      The k field contains the field name in the original document.
     *      The v field contains the value of the field in the original document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/objectToArray/
     *
     * @param mixed|Expr $object
     */
    public function objectToArray($object): self
    {
        $this->expr->objectToArray($object);

        return $this;
    }

    /**
     * Adds one or more $or clause to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/or/
     * @see Expr::or
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function or($expression, ...$expressions): self
    {
        $this->expr->or($expression, ...$expressions);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function reduce($input, $initialValue, $in): self
    {
        $this->expr->reduce($input, $initialValue, $in);

        return $this;
    }

    /**
     * Provides regular expression (regex) pattern matching capability in
     * aggregation expressions.
     *
     * If a match is found, returns a document that contains information on the
     * first match. If a match is not found, returns null.
     *
     * @param mixed|Expr  $input
     * @param mixed|Expr  $regex
     * @param string|null $options
     */
    public function regexFind($input, $regex, $options = null): self
    {
        $this->expr->regexFind($input, $regex, $options);

        return $this;
    }

    /**
     * Provides regular expression (regex) pattern matching capability in
     * aggregation expressions.
     *
     * The operator returns an array of documents that contains information on
     * each match. If a match is not found, returns an empty array.
     *
     * @param mixed|Expr  $input
     * @param mixed|Expr  $regex
     * @param string|null $options
     */
    public function regexFindAll($input, $regex, $options = null): self
    {
        $this->expr->regexFindAll($input, $regex, $options);

        return $this;
    }

    /**
     * Performs a regular expression (regex) pattern matching and returns true
     * if a match exists.
     *
     * @param mixed|Expr  $input
     * @param mixed|Expr  $regex
     * @param string|null $options
     */
    public function regexMatch($input, $regex, $options = null): self
    {
        $this->expr->regexMatch($input, $regex, $options);

        return $this;
    }

    /**
     * Replaces all instances of a search string in an input string with a
     * replacement string.
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $find
     * @param mixed|Expr $replacement
     */
    public function replaceAll($input, $find, $replacement): self
    {
        $this->expr->replaceAll($input, $find, $replacement);

        return $this;
    }

    /**
     * Replaces the first instance of a search string in an input string with a
     * replacement string. If no occurrences are found, it evaluates to the
     * input string.
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $find
     * @param mixed|Expr $replacement
     */
    public function replaceOne($input, $find, $replacement): self
    {
        $this->expr->replaceOne($input, $find, $replacement);

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
     *
     * @return static
     */
    public function reverseArray($expression): self
    {
        $this->expr->reverseArray($expression);

        return $this;
    }

    /**
     * Removes whitespace characters, including null, or the specified characters from the end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rtrim/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $chars
     */
    public function rtrim($input, $chars = null): self
    {
        $this->expr->rtrim($input, $chars);

        return $this;
    }

    /**
     * Rounds a number to a whole integer or to a specified decimal place.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/round/
     *
     * @param mixed|Expr      $number
     * @param mixed|Expr|null $place
     */
    public function round($number, $place = null): self
    {
        $this->expr->round($number, $place);

        return $this;
    }

    /**
     * Converts a value from radians to degrees.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/radiansToDegrees/
     *
     * @param mixed|Expr $expression
     */
    public function radiansToDegrees($expression): self
    {
        $this->expr->radiansToDegrees($expression);

        return $this;
    }

    /**
     * Returns a random float between 0 and 1 each time it is called.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rand/
     * @see Expr::rand
     */
    public function rand(): self
    {
        $this->expr->rand();

        return $this;
    }

    /**
     * Matches a random selection of input documents. The number of documents selected approximates the sample rate expressed as a percentage of the total number of documents.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sampleRate/
     * @see Expr::sampleRate
     */
    public function sampleRate(float $rate): self
    {
        $this->expr->sampleRate($rate);

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
     *
     * @return static
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
     *
     * @return static
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
     * @return static
     */
    public function setEquals($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setEquals(...func_get_args());

        return $this;
    }

    /**
     * Adds, updates, or removes a specified field in a document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setField/
     * @see Expr::setField
     *
     * @param mixed|Expr $field
     * @param mixed|Expr $input
     * @param mixed|Expr $value
     */
    public function setField($field, $input, $value): self
    {
        $this->expr->setField($field, $input, $value);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function setUnion($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setUnion(...func_get_args());

        return $this;
    }

    /**
     * Returns the sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sin/
     *
     * @param mixed|Expr $expression
     */
    public function sin($expression): self
    {
        $this->expr->sin($expression);

        return $this;
    }

    /**
     * Returns the hyperbolic sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sinh/
     *
     * @param mixed|Expr $expression
     */
    public function sinh($expression): self
    {
        $this->expr->sinh($expression);

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
     *
     * @return static
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
     *
     * @return static
     */
    public function slice($array, $n, $position = null): self
    {
        $this->expr->slice($array, $n, $position);

        return $this;
    }

    /**
     * Sorts an array based on its elements. The sort order is user specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sortArray/
     * @see Expr::sortArray
     *
     * @param mixed|Expr                $input
     * @param array<string, int|string> $sortBy
     */
    public function sortArray($input, $sortBy): self
    {
        $this->expr->sortArray($input, $sortBy);

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
     *
     * @return static
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
     *
     * @return static
     */
    public function sqrt($expression): self
    {
        $this->expr->sqrt($expression);

        return $this;
    }

    /**
     * Calculates the population standard deviation of the input values. Use if
     * the values encompass the entire population of data you want to represent
     * and do not wish to generalize about a larger population. $stdDevPop
     * ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     * @see Expr::stdDevPop
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function stdDevPop($expression, ...$expressions): self
    {
        $this->expr->stdDevPop($expression, ...$expressions);

        return $this;
    }

    /**
     * Calculates the sample standard deviation of the input values. Use if the
     * values encompass a sample of a population of data from which to
     * generalize about the population. $stdDevSamp ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     * @see Expr::stdDevSamp
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function stdDevSamp($expression, ...$expressions): self
    {
        $this->expr->stdDevSamp($expression, ...$expressions);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function subtract($expression1, $expression2): self
    {
        $this->expr->subtract($expression1, $expression2);

        return $this;
    }

    /**
     * Calculates the collective sum of numeric values. Ignores non-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     * @see Expr::sum
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr ...$expressions
     */
    public function sum($expression, ...$expressions): self
    {
        $this->expr->sum($expression, ...$expressions);

        return $this;
    }

    /**
     * Evaluates a series of case expressions. When it finds an expression which
     * evaluates to true, $switch executes a specified expression and breaks out
     * of the control flow.
     *
     * To add statements, use the {@link case()}, {@link then()} and
     * {@link default()} methods.
     */
    public function switch(): self
    {
        $this->expr->switch();

        return $this;
    }

    /**
     * Returns the tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tan/
     *
     * @param mixed|Expr $expression
     */
    public function tan($expression): self
    {
        $this->expr->tan($expression);

        return $this;
    }

    /**
     * Returns the hyperbolic tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tanh/
     *
     * @param mixed|Expr $expression
     */
    public function tanh($expression): self
    {
        $this->expr->tanh($expression);

        return $this;
    }

    /**
     * Adds a case statement for the current branch of the $switch operator.
     *
     * Requires {@link case()} to be called first. The argument can be any valid
     * expression.
     *
     * @param mixed|Expr $expression
     */
    public function then($expression): self
    {
        $this->expr->then($expression);

        return $this;
    }

    /**
     * Converts value to a boolean.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toBool/
     *
     * @param mixed|Expr $expression
     */
    public function toBool($expression): self
    {
        $this->expr->toBool($expression);

        return $this;
    }

    /**
     * Converts value to a Date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDate/
     *
     * @param mixed|Expr $expression
     */
    public function toDate($expression): self
    {
        $this->expr->toDate($expression);

        return $this;
    }

    /**
     * Converts value to a Decimal128.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDecimal/
     *
     * @param mixed|Expr $expression
     */
    public function toDecimal($expression): self
    {
        $this->expr->toDecimal($expression);

        return $this;
    }

    /**
     * Converts value to a double.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDouble/
     *
     * @param mixed|Expr $expression
     */
    public function toDouble($expression): self
    {
        $this->expr->toDouble($expression);

        return $this;
    }

    /**
     * Converts value to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toInt/
     *
     * @param mixed|Expr $expression
     */
    public function toInt($expression): self
    {
        $this->expr->toInt($expression);

        return $this;
    }

    /**
     * Converts value to a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLong/
     *
     * @param mixed|Expr $expression
     */
    public function toLong($expression): self
    {
        $this->expr->toLong($expression);

        return $this;
    }

    /**
     * Converts value to an ObjectId.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toObjectId/
     *
     * @param mixed|Expr $expression
     */
    public function toObjectId($expression): self
    {
        $this->expr->toObjectId($expression);

        return $this;
    }

    /**
     * Converts value to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toString/
     *
     * @param mixed|Expr $expression
     */
    public function toString($expression): self
    {
        $this->expr->toString($expression);

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
     *
     * @return static
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
     *
     * @return static
     */
    public function toUpper($expression): self
    {
        $this->expr->toUpper($expression);

        return $this;
    }

    /**
     * Removes whitespace characters, including null, or the specified characters from
     * the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trim/
     *
     * @param mixed|Expr      $input
     * @param mixed|Expr|null $chars
     */
    public function trim($input, $chars = null): self
    {
        $this->expr->trim($input, $chars);

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
     *
     * @return static
     */
    public function trunc($number): self
    {
        $this->expr->trunc($number);

        return $this;
    }

    /**
     * Returns the incrementing ordinal from a timestamp as a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tsIncrement/
     * @see Expr::tsIncrement
     *
     * @param mixed|Expr $expression
     */
    public function tsIncrement($expression): self
    {
        $this->expr->tsIncrement($expression);

        return $this;
    }

    /**
     * Returns the seconds from a timestamp as a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tsSecond/
     * @see Expr::tsSecond
     *
     * @param mixed|Expr $expression
     */
    public function tsSecond($expression): self
    {
        $this->expr->tsSecond($expression);

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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): self
    {
        $this->expr->zip($inputs, $useLongestLength, $defaults);

        return $this;
    }
}
