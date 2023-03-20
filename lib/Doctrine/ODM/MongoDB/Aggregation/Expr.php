<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Aggregation\Operator\AccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArithmeticOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArrayOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\BooleanOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ConditionalOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\CustomOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\DataSizeOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\DateOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\GroupAccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\MiscOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ObjectOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\SetOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\StringOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\TimestampOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\TrigonometryOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\TypeOperators;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Types\Type;
use LogicException;
use MongoDB\BSON\Javascript;

use function array_filter;
use function array_map;
use function array_merge;
use function count;
use function func_get_args;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function substr;

use const ARRAY_FILTER_USE_BOTH;

/**
 * Fluent interface for building aggregation pipelines.
 *
 * @psalm-type OperatorExpression = array<string, mixed>|object
 */
class Expr implements
    AccumulatorOperators,
    ArithmeticOperators,
    ArrayOperators,
    BooleanOperators,
    ConditionalOperators,
    CustomOperators,
    DataSizeOperators,
    DateOperators,
    GroupAccumulatorOperators,
    MiscOperators,
    ObjectOperators,
    SetOperators,
    StringOperators,
    TimestampOperators,
    TrigonometryOperators,
    TypeOperators
{
    /** @var array<string, mixed> */
    private array $expr = [];

    /**
     * The current field we are operating on.
     */
    private ?string $currentField = null;

    /** @var array{case: mixed|self, then?: mixed|self}|null */
    private ?array $switchBranch = null;

    public function __construct(private DocumentManager $dm, private ClassMetadata $class)
    {
    }

    /**
     * Returns the absolute value of a number.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/abs/
     *
     * @param mixed|self $number
     */
    public function abs($number): self
    {
        return $this->operator('$abs', $number);
    }

    /**
     * Defines a custom accumulator operator.
     *
     * Accumulators are operators that maintain their state (e.g. totals,
     * maximums, minimums, and related data) as documents progress through the
     * pipeline. Use the $accumulator operator to execute your own JavaScript
     * functions to implement behavior not supported by the MongoDB Query
     * Language.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/accumulator/
     *
     * @param string|Javascript $init
     * @param mixed|self        $initArgs
     * @param string|Javascript $accumulate
     * @param mixed|self        $accumulateArgs
     * @param string|Javascript $merge
     * @param string|Javascript $finalize
     * @param string            $lang
     */
    public function accumulator($init, $initArgs, $accumulate, $accumulateArgs, $merge, $finalize = null, $lang = 'js'): self
    {
        return $this->operator('$accumulator', $this->filterOptionalNullArguments([
            'init' => $init,
            'initArgs' => $initArgs,
            'accumulate' => $accumulate,
            'accumulateArgs' => $accumulateArgs,
            'merge' => $merge,
            'finalize' => $finalize,
            'lang' => $lang,
        ], ['initArgs', 'finalize']));
    }

    /**
     * Adds numbers together or adds numbers and a date. If one of the arguments
     * is a date, $add treats the other arguments as milliseconds to add to the
     * date.
     *
     * The arguments can be any valid expression as long as they resolve to
     * either all numbers or to numbers and a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/add/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self ...$expressions Additional expressions
     */
    public function add($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$add', func_get_args());
    }

    /**
     * Adds one or more $and clauses to the current expression.
     *
     * @deprecated Use and() instead
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/and/
     *
     * @param array<string, mixed>|self $expression
     * @param array<string, mixed>|self ...$expressions
     */
    public function addAnd($expression, ...$expressions): self
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
     * @deprecated Use or() instead
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/or/
     *
     * @param array<string, mixed>|self $expression
     * @param array<string, mixed>|self ...$expressions
     */
    public function addOr($expression, ...$expressions): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/addToSet/
     *
     * @param mixed|self $expression
     */
    public function addToSet($expression): self
    {
        return $this->operator('$addToSet', $expression);
    }

    /**
     * Evaluates an array as a set and returns true if no element in the array
     * is false. Otherwise, returns false. An empty array returns true.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/allElementsTrue/
     *
     * @param mixed|self $expression
     */
    public function allElementsTrue($expression): self
    {
        return $this->operator('$allElementsTrue', $expression);
    }

    /**
     * Adds one or more $and clauses to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/and/
     *
     * @param array<string, mixed>|self $expression
     * @param array<string, mixed>|self ...$expressions
     */
    public function and($expression, ...$expressions): self
    {
        return $this->operator('$and', func_get_args());
    }

    /**
     * Evaluates an array as a set and returns true if any of the elements are
     * true and false otherwise. An empty array returns false.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/anyElementTrue/
     *
     * @param mixed[]|self $expression
     */
    public function anyElementTrue($expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayElemAt/
     *
     * @param mixed|self $array
     * @param mixed|self $index
     */
    public function arrayElemAt($array, $index): self
    {
        return $this->operator('$arrayElemAt', [$array, $index]);
    }

    /**
     * Returns the average value of the numeric values that result from applying
     * a specified expression to each document in a group of documents that
     * share the same group by key. Ignores nun-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/avg/
     *
     * @param mixed|self $expression
     * @param mixed|self ...$expressions
     */
    public function avg($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$avg', $expression, ...$expressions);
    }

    /**
     * Returns the size of a given string or binary data value's content in bytes.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/binarySize/
     *
     * @param mixed|self $expression
     */
    public function binarySize($expression): self
    {
        return $this->operator('$binarySize', $expression);
    }

    /**
     * Returns the bottom element within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottom/
     *
     * @param mixed|self                $output
     * @param array<string, int|string> $sortBy
     */
    public function bottom($output, $sortBy): self
    {
        return $this->operator('$bottom', ['output' => $output, 'sortBy' => $sortBy]);
    }

    /**
     * Returns the n bottom elements within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bottomN/
     *
     * @param mixed|self                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|self                $n
     */
    public function bottomN($output, $sortBy, $n): self
    {
        return $this->operator('$bottomN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    /**
     * Returns the size in bytes of a given document (i.e. bsontype Object) when encoded as BSON.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/bsonSize/
     *
     * @param mixed|self $expression
     */
    public function bsonSize($expression): self
    {
        return $this->operator('$bsonSize', $expression);
    }

    /**
     * Adds a case statement for a branch of the $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression that resolves to a boolean. If the result is not a
     * boolean, it is coerced to a boolean value.
     *
     * @param mixed|self $expression
     */
    public function case($expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ceil/
     *
     * @param mixed|self $number
     */
    public function ceil($number): self
    {
        return $this->operator('$ceil', $number);
    }

    /**
     * Compares two values and returns:
     * -1 if the first value is less than the second.
     * 1 if the first value is greater than the second.
     * 0 if the two values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cmp/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function cmp($expression1, $expression2): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/concat/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self ...$expressions Additional expressions
     */
    public function concat($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$concat', func_get_args());
    }

    /**
     * Concatenates arrays to return the concatenated array.
     *
     * The <array> expressions can be any valid expression as long as they
     * resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/concatArrays/
     *
     * @param mixed|self $array1
     * @param mixed|self $array2
     * @param mixed|self ...$arrays Additional expressions
     */
    public function concatArrays($array1, $array2, ...$arrays): self
    {
        return $this->operator('$concatArrays', func_get_args());
    }

    /**
     * Evaluates a boolean expression to return one of the two specified return
     * expressions.
     *
     * The arguments can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cond/
     *
     * @param mixed|self $if
     * @param mixed|self $then
     * @param mixed|self $else
     */
    public function cond($if, $then, $else): self
    {
        return $this->operator('$cond', ['if' => $if, 'then' => $then, 'else' => $else]);
    }

    /**
     * Converts an expression object into an array, recursing into nested items.
     *
     * For expression objects, it calls getExpression on the expression object.
     * For arrays, it recursively calls itself for each array item. Other values
     * are returned directly.
     *
     * @internal
     *
     * @param mixed|self $expression
     *
     * @return string|array<string, mixed>
     */
    public static function convertExpression($expression)
    {
        if (is_array($expression)) {
            return array_map(static fn ($expression) => static::convertExpression($expression), $expression);
        }

        if ($expression instanceof self) {
            return $expression->getExpression();
        }

        return $expression;
    }

    /**
     * Returns the number of documents in a group.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/count/
     */
    public function countDocuments(): self
    {
        return $this->operator('$count', []);
    }

    /**
     * Increments a Date object by a specified number of time units
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateAdd/
     *
     * @param mixed|self $startDate
     * @param mixed|self $unit
     * @param mixed|self $amount
     * @param mixed|self $timezone
     */
    public function dateAdd($startDate, $unit, $amount, $timezone = null): self
    {
        return $this->operator('$dateAdd', $this->filterOptionalNullArguments([
            'startDate' => $startDate,
            'unit' => $unit,
            'amount' => $amount,
            'timezone' => $timezone,
        ], ['timezone']));
    }

    /**
     * Returns the difference between two dates
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateDiff/
     *
     * @param mixed|self $startDate
     * @param mixed|self $endDate
     * @param mixed|self $unit
     * @param mixed|self $timezone
     * @param mixed|self $startOfWeek
     */
    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): self
    {
        return $this->operator('$dateDiff', $this->filterOptionalNullArguments([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'unit' => $unit,
            'timezone' => $timezone,
            'startOfWeek' => $startOfWeek,
        ], ['timezone', 'startOfWeek']));
    }

    /**
     * Constructs and returns a Date object given the date's constituent properties
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromParts/
     *
     * @param mixed|self $year
     * @param mixed|self $isoWeekYear
     * @param mixed|self $month
     * @param mixed|self $isoWeek
     * @param mixed|self $day
     * @param mixed|self $isoDayOfWeek
     * @param mixed|self $hour
     * @param mixed|self $minute
     * @param mixed|self $second
     * @param mixed|self $millisecond
     * @param mixed|self $timezone
     */
    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): self
    {
        return $this->operator('$dateFromParts', $this->filterOptionalNullArguments([
            'year' => $year,
            'isoWeekYear' => $isoWeekYear,
            'month' => $month,
            'isoWeek' => $isoWeek,
            'day' => $day,
            'isoDayOfWeek' => $isoDayOfWeek,
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
            'millisecond' => $millisecond,
            'timezone' => $timezone,
        ], [
            'year',
            'isoWeekYear',
            'month',
            'isoWeek',
            'day',
            'isoDayOfWeek',
            'hour',
            'minute',
            'second',
            'millisecond',
            'timezone',
        ]));
    }

    /**
     * Converts a date/time string to a date object.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromString/
     *
     * @param mixed|self $dateString
     * @param mixed|self $format
     * @param mixed|self $timezone
     * @param mixed|self $onError
     * @param mixed|self $onNull
     */
    public function dateFromString($dateString, $format = null, $timezone = null, $onError = null, $onNull = null): self
    {
        return $this->operator(
            '$dateFromString',
            $this->filterOptionalNullArguments(
                [
                    'dateString' => $dateString,
                    'format' => $format,
                    'timezone' => $timezone,
                    'onError' => $onError,
                    'onNull' => $onNull,
                ],
                [
                    'format',
                    'timezone',
                    'onError',
                    'onNull',
                ],
            ),
        );
    }

    /**
     * Decrements a Date object by a specified number of time units
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateSubtract/
     *
     * @param mixed|self $startDate
     * @param mixed|self $unit
     * @param mixed|self $amount
     * @param mixed|self $timezone
     */
    public function dateSubtract($startDate, $unit, $amount, $timezone = null): self
    {
        return $this->operator('$dateSubtract', $this->filterOptionalNullArguments([
            'startDate' => $startDate,
            'unit' => $unit,
            'amount' => $amount,
            'timezone' => $timezone,
        ], ['timezone']));
    }

    /**
     * Returns a document that contains the constituent parts of a given BSON Date value as individual properties. The properties returned are year, month, day, hour, minute, second and millisecond.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToParts/
     *
     * @param mixed|self $date
     * @param mixed|self $timezone
     * @param mixed|self $iso8601
     */
    public function dateToParts($date, $timezone = null, $iso8601 = null): self
    {
        return $this->operator('$dateToParts', $this->filterOptionalNullArguments([
            'date' => $date,
            'timezone' => $timezone,
            'iso8601' => $iso8601,
        ], ['timezone', 'iso8601']));
    }

    /**
     * Converts a date object to a string according to a user-specified format.
     *
     * The format string can be any string literal, containing 0 or more format
     * specifiers.
     * The date argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToString/
     *
     * @param mixed|self      $expression
     * @param mixed|self|null $timezone
     * @param mixed|self|null $onNull
     */
    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): self
    {
        return $this->operator('$dateToString', $this->filterOptionalNullArguments([
            'date' => $expression,
            'format' => $format,
            'timezone' => $timezone,
            'onNull' => $onNull,
        ], ['timezone', 'onNull']));
    }

    /**
     * Truncates a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateTrunc/
     *
     * @param mixed|self $date
     * @param mixed|self $unit
     * @param mixed|self $binSize
     * @param mixed|self $timezone
     * @param mixed|self $startOfWeek
     */
    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): self
    {
        return $this->operator('$dateTrunc', $this->filterOptionalNullArguments([
            'date' => $date,
            'unit' => $unit,
            'binSize' => $binSize,
            'timezone' => $timezone,
            'startOfWeek' => $startOfWeek,
        ], ['binSize', 'timezone', 'startOfWeek']));
    }

    /**
     * Returns the day of the month for a date as a number between 1 and 31.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfMonth/
     *
     * @param mixed|self $expression
     */
    public function dayOfMonth($expression): self
    {
        return $this->operator('$dayOfMonth', $expression);
    }

    /**
     * Returns the day of the week for a date as a number between 1 (Sunday) and
     * 7 (Saturday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfWeek/
     *
     * @param mixed|self $expression
     */
    public function dayOfWeek($expression): self
    {
        return $this->operator('$dayOfWeek', $expression);
    }

    /**
     * Returns the day of the year for a date as a number between 1 and 366.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfYear/
     *
     * @param mixed|self $expression
     */
    public function dayOfYear($expression): self
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
     */
    public function default($expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/divide/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function divide($expression1, $expression2): self
    {
        return $this->operator('$divide', [$expression1, $expression2]);
    }

    /**
     * Compares two values and returns whether the are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/eq/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function eq($expression1, $expression2): self
    {
        return $this->operator('$eq', [$expression1, $expression2]);
    }

    /**
     * Raises Euler’s number to the specified exponent and returns the result.
     *
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/exp/
     *
     * @param mixed|self $exponent
     */
    public function exp($exponent): self
    {
        return $this->operator('$exp', $exponent);
    }

    /**
     * Returns a new expression object.
     */
    public function expr(): self
    {
        return new static($this->dm, $this->class);
    }

    /**
     * Allows any expression to be used as a field value.
     *
     * @see https://docs.mongodb.com/manual/meta/aggregation-quick-reference/#aggregation-expressions
     *
     * @param mixed|self $value
     */
    public function expression($value): self
    {
        if (! $this->currentField) {
            throw new LogicException(sprintf('%s requires setting a current field using field().', __METHOD__));
        }

        $this->expr[$this->currentField] = $this->ensureArray($value);

        return $this;
    }

    /**
     * Set the current field for building the expression.
     */
    public function field(string $fieldName): self
    {
        $fieldName          = $this->getDocumentPersister()->prepareFieldName($fieldName);
        $this->currentField = $fieldName;

        return $this;
    }

    /**
     * Selects a subset of the array to return based on the specified condition.
     *
     * Returns an array with only those elements that match the condition. The
     * returned elements are in the original order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/filter/
     *
     * @param mixed|self $input
     * @param mixed|self $as
     * @param mixed|self $cond
     */
    public function filter($input, $as, $cond): self
    {
        return $this->operator('$filter', ['input' => $input, 'as' => $as, 'cond' => $cond]);
    }

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents that share the same group by key. Only
     * meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/first/
     *
     * @param mixed|self $expression
     */
    public function first($expression): self
    {
        return $this->operator('$first', $expression);
    }

    /**
     * Returns a specified number of elements from the beginning of an array. Distinct from the $firstN accumulator.
     *
     * @see https://www.mongodb.com/docs/manual/reference/operator/aggregation/firstN-array-element/
     *
     * @param mixed|self $expression
     * @param mixed|self $n
     */
    public function firstN($expression, $n): self
    {
        return $this->operator('$firstN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /**
     * Defines a custom aggregation function or expression in JavaScript.
     *
     * You can use the $function operator to define custom functions to
     * implement behavior not supported by the MongoDB Query Language.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/function/
     *
     * @param string|Javascript $body
     * @param mixed|self        $args
     * @param string            $lang
     */
    public function function($body, $args, $lang = 'js'): self
    {
        return $this->operator('$function', ['body' => $body, 'args' => $args, 'lang' => $lang]);
    }

    /**
     * Returns the largest integer less than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/floor/
     *
     * @param mixed|self $number
     */
    public function floor($number): self
    {
        return $this->operator('$floor', $number);
    }

    /** @return array<string, mixed> */
    public function getExpression(): array
    {
        return $this->expr;
    }

    /**
     * Returns the value of a specified field from a document. If you don't specify an object, $getField returns the value of the field from $$CURRENT.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/getField/
     *
     * @param mixed|self $field
     * @param mixed|self $input
     */
    public function getField($field, $input = null): self
    {
        return $this->operator('$getField', $this->filterOptionalNullArguments([
            'field' => $field,
            'input' => $input,
        ], ['input']));
    }

    /**
     * Compares two values and returns:
     * true when the first value is greater than the second value.
     * false when the first value is less than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gt/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function gt($expression1, $expression2): self
    {
        return $this->operator('$gt', [$expression1, $expression2]);
    }

    /**
     * Compares two values and returns:
     * true when the first value is greater than or equivalent to the second
     * value.
     * false when the first value is less than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gte/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function gte($expression1, $expression2): self
    {
        return $this->operator('$gte', [$expression1, $expression2]);
    }

    /**
     * Returns the hour portion of a date as a number between 0 and 23.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/hour/
     *
     * @param mixed|self $expression
     */
    public function hour($expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ifNull/
     *
     * @param mixed|self $expression
     * @param mixed|self $replacementExpression
     */
    public function ifNull($expression, $replacementExpression): self
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
     *
     * @param mixed|self $expression
     * @param mixed|self $arrayExpression
     */
    public function in($expression, $arrayExpression): self
    {
        return $this->operator('$in', [$expression, $arrayExpression]);
    }

    /**
     * Searches an array for an occurrence of a specified value and returns the
     * array index (zero-based) of the first occurrence. If the value is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfArray/
     *
     * @param mixed|self $arrayExpression  can be any valid expression as long as it resolves to an array
     * @param mixed|self $searchExpression can be any valid expression
     * @param mixed|self $start            Optional. An integer, or a number that can be represented as integers (such as 2.0), that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param mixed|self $end              An integer, or a number that can be represented as integers (such as 2.0), that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null): self
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
     * Searches a string for an occurrence of a substring and returns the UTF-8
     * byte index (zero-based) of the first occurrence. If the substring is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfBytes/
     *
     * @param mixed|self      $stringExpression    can be any valid expression as long as it resolves to a string
     * @param mixed|self      $substringExpression can be any valid expression as long as it resolves to a string
     * @param string|int|null $start               An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param string|int|null $end                 An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): self
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
     * Searches a string for an occurrence of a substring and returns the UTF-8
     * code point index (zero-based) of the first occurrence. If the substring is
     * not found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfCP/
     *
     * @param mixed|self      $stringExpression    can be any valid expression as long as it resolves to a string
     * @param mixed|self      $substringExpression can be any valid expression as long as it resolves to a string
     * @param string|int|null $start               An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param string|int|null $end                 An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isArray/
     *
     * @param mixed|self $expression
     */
    public function isArray($expression): self
    {
        return $this->operator('$isArray', $expression);
    }

    /**
     * Returns the weekday number in ISO 8601 format, ranging from 1 (for Monday)
     * to 7 (for Sunday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoDayOfWeek/
     *
     * @param mixed|self $expression
     */
    public function isoDayOfWeek($expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoWeek/
     *
     * @param mixed|self $expression
     */
    public function isoWeek($expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoWeek/
     *
     * @param mixed|self $expression
     */
    public function isoWeekYear($expression): self
    {
        return $this->operator('$isoWeekYear', $expression);
    }

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents that share the same group by a field.
     * Only meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     *
     * @param mixed|self $expression
     */
    public function last($expression): self
    {
        return $this->operator('$last', $expression);
    }

    /**
     * Returns a specified number of elements from the end of an array. Distinct from the $lastN accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lastN-array-element/
     *
     * @param mixed|self $expression
     * @param mixed|self $n
     */
    public function lastN($expression, $n): self
    {
        return $this->operator('$lastN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /**
     * Binds variables for use in the specified expression, and returns the
     * result of the expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/let/
     *
     * @param mixed|self $vars Assignment block for the variables accessible in the in expression. To assign a variable, specify a string for the variable name and assign a valid expression for the value.
     * @param mixed|self $in   the expression to evaluate
     */
    public function let($vars, $in): self
    {
        return $this->operator('$let', ['vars' => $vars, 'in' => $in]);
    }

    /**
     * Returns a value without parsing. Use for values that the aggregation
     * pipeline may interpret as an expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/literal/
     *
     * @param mixed|self $value
     */
    public function literal($value): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     *
     * @param mixed|self $number
     */
    public function ln($number): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     *
     * @param mixed|self $number
     * @param mixed|self $base
     */
    public function log($number, $base): self
    {
        return $this->operator('$log', [$number, $base]);
    }

    /**
     * Calculates the log base 10 of a number and returns the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log10/
     *
     * @param mixed|self $number
     */
    public function log10($number): self
    {
        return $this->operator('$log10', $number);
    }

    /**
     * Compares two values and returns:
     * true when the first value is less than the second value.
     * false when the first value is greater than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lt/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function lt($expression1, $expression2): self
    {
        return $this->operator('$lt', [$expression1, $expression2]);
    }

    /**
     * Compares two values and returns:
     * true when the first value is less than or equivalent to the second value.
     * false when the first value is greater than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lte/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function lte($expression1, $expression2): self
    {
        return $this->operator('$lte', [$expression1, $expression2]);
    }

    /**
     * Applies an expression to each item in an array and returns an array with
     * the applied results.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/map/
     *
     * @param mixed|self $input an expression that resolves to an array
     * @param string     $as    The variable name for the items in the input array. The in expression accesses each item in the input array by this variable.
     * @param mixed|self $in    The expression to apply to each item in the input array. The expression accesses the item by its variable name.
     */
    public function map($input, $as, $in): self
    {
        return $this->operator('$map', ['input' => $input, 'as' => $as, 'in' => $in]);
    }

    /**
     * Returns the highest value that results from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/max/
     *
     * @param mixed|self $expression
     * @param mixed|self $expressions
     */
    public function max($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$max', $expression, ...$expressions);
    }

    /**
     * Returns the n largest values in an array. Distinct from the $maxN accumulator.
     *
     * @param mixed|self $expression
     * @param mixed|self $n
     */
    public function maxN($expression, $n): self
    {
        return $this->operator('$maxN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /**
     * Combines multiple documents into a single document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mergeObjects/
     *
     * @param mixed|self $expression
     * @param mixed|self ...$expressions
     */
    public function mergeObjects($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$mergeObjects', $expression, ...$expressions);
    }

    /**
     * Returns the metadata associated with a document in a pipeline operations.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/meta/
     *
     * @param mixed|self $metaDataKeyword
     */
    public function meta($metaDataKeyword): self
    {
        return $this->operator('$meta', $metaDataKeyword);
    }

    /**
     * Returns the millisecond portion of a date as an integer between 0 and 999.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/millisecond/
     *
     * @param mixed|self $expression
     */
    public function millisecond($expression): self
    {
        return $this->operator('$millisecond', $expression);
    }

    /**
     * Returns the lowest value that results from applying an expression to each
     * document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/min/
     *
     * @param mixed|self $expression
     * @param mixed|self $expressions
     */
    public function min($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$min', $expression, ...$expressions);
    }

    /**
     * Returns the n smallest values in an array. Distinct from the $minN accumulator.
     *
     * @param mixed|self $expression
     * @param mixed|self $n
     */
    public function minN($expression, $n): self
    {
        return $this->operator('$minN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /**
     * Returns the minute portion of a date as a number between 0 and 59.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minute/
     *
     * @param mixed|self $expression
     */
    public function minute($expression): self
    {
        return $this->operator('$minute', $expression);
    }

    /**
     * Divides one number by another and returns the remainder. The first
     * argument is divided by the second argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mod/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function mod($expression1, $expression2): self
    {
        return $this->operator('$mod', [$expression1, $expression2]);
    }

    /**
     * Returns the month of a date as a number between 1 and 12.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/month/
     *
     * @param mixed|self $expression
     */
    public function month($expression): self
    {
        return $this->operator('$month', $expression);
    }

    /**
     * Multiplies numbers together and returns the result.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/multiply/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self ...$expressions Additional expressions
     */
    public function multiply($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$multiply', func_get_args());
    }

    /**
     * Compares two values and returns:
     * true when the values are not equivalent.
     * false when the values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ne/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function ne($expression1, $expression2): self
    {
        return $this->operator('$ne', [$expression1, $expression2]);
    }

    /**
     * Evaluates a boolean and returns the opposite boolean value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/not/
     *
     * @param mixed|self $expression
     */
    public function not($expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/pow/
     *
     * @param mixed|self $number
     * @param mixed|self $exponent
     */
    public function pow($number, $exponent): self
    {
        return $this->operator('$pow', [$number, $exponent]);
    }

    /**
     * Returns an array of all values that result from applying an expression to
     * each document in a group of documents that share the same group by key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/push/
     *
     * @param mixed|self $expression
     */
    public function push($expression): self
    {
        return $this->operator('$push', $expression);
    }

    /**
     * Returns a random float between 0 and 1 each time it is called.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rand/
     */
    public function rand(): self
    {
        return $this->operator('$rand', []);
    }

    /**
     * Returns an array whose elements are a generated sequence of numbers.
     *
     * $range generates the sequence from the specified starting number by successively incrementing the starting number by the specified step value up to but not including the end point.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/range/
     *
     * @param mixed|self $start An integer that specifies the start of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|self $end   An integer that specifies the exclusive upper limit of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|self $step  Optional. An integer that specifies the increment value. Can be any valid expression that resolves to a non-zero integer. Defaults to 1.
     */
    public function range($start, $end, $step = 1): self
    {
        return $this->operator('$range', [$start, $end, $step]);
    }

    /**
     * Applies an expression to each element in an array and combines them into
     * a single value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reduce/
     *
     * @param mixed|self $input        can be any valid expression that resolves to an array
     * @param mixed|self $initialValue the initial cumulative value set before in is applied to the first element of the input array
     * @param mixed|self $in           A valid expression that $reduce applies to each element in the input array in left-to-right order. Wrap the input value with $reverseArray to yield the equivalent of applying the combining expression from right-to-left.
     */
    public function reduce($input, $initialValue, $in): self
    {
        return $this->operator('$reduce', ['input' => $input, 'initialValue' => $initialValue, 'in' => $in]);
    }

    /**
     * Accepts an array expression as an argument and returns an array with the
     * elements in reverse order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reverseArray/
     *
     * @param mixed|self $expression
     */
    public function reverseArray($expression): self
    {
        return $this->operator('$reverseArray', $expression);
    }

    /**
     * Matches a random selection of input documents. The number of documents selected approximates the sample rate expressed as a percentage of the total number of documents.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sampleRate/
     */
    public function sampleRate(float $rate): self
    {
        return $this->operator('$sampleRate', $rate);
    }

    /**
     * Returns the second portion of a date as a number between 0 and 59, but
     * can be 60 to account for leap seconds.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/second/
     *
     * @param mixed|self $expression
     */
    public function second($expression): self
    {
        return $this->operator('$second', $expression);
    }

    /**
     * Takes two sets and returns an array containing the elements that only
     * exist in the first set.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setDifference/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function setDifference($expression1, $expression2): self
    {
        return $this->operator('$setDifference', [$expression1, $expression2]);
    }

    /**
     * Compares two or more arrays and returns true if they have the same
     * distinct elements and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setEquals/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self ...$expressions Additional sets
     */
    public function setEquals($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$setEquals', func_get_args());
    }

    /**
     * Adds, updates, or removes a specified field in a document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setField/
     *
     * @param mixed|self $field
     * @param mixed|self $input
     * @param mixed|self $value
     */
    public function setField($field, $input, $value): self
    {
        return $this->operator('$setField', ['field' => $field, 'input' => $input, 'value' => $value]);
    }

    /**
     * Takes two or more arrays and returns an array that contains the elements
     * that appear in every input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIntersection/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self ...$expressions Additional sets
     */
    public function setIntersection($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$setIntersection', func_get_args());
    }

    /**
     * Takes two arrays and returns true when the first array is a subset of the
     * second, including when the first array equals the second array, and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIsSubset/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function setIsSubset($expression1, $expression2): self
    {
        return $this->operator('$setIsSubset', [$expression1, $expression2]);
    }

    /**
     * Takes two or more arrays and returns an array containing the elements
     * that appear in any input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setUnion/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     * @param mixed|self ...$expressions Additional sets
     */
    public function setUnion($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$setUnion', func_get_args());
    }

    /**
     * Counts and returns the total the number of items in an array.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/size/
     *
     * @param mixed|self $expression
     */
    public function size($expression): self
    {
        return $this->operator('$size', $expression);
    }

    /**
     * Returns a subset of an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/slice/
     *
     * @param mixed|self      $array
     * @param mixed|self      $n
     * @param mixed|self|null $position
     */
    public function slice($array, $n, $position = null): self
    {
        if ($position === null) {
            return $this->operator('$slice', [$array, $n]);
        }

        return $this->operator('$slice', [$array, $position, $n]);
    }

    /**
     * Sorts an array based on its elements. The sort order is user specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sortArray/
     *
     * @param mixed|self                $input
     * @param array<string, int|string> $sortBy
     */
    public function sortArray($input, $sortBy): self
    {
        return $this->operator('$sortArray', [
            'input' => $input,
            'sortBy' => $sortBy,
        ]);
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
     * @param mixed|self $string    The string to be split. Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $delimiter The delimiter to use when splitting the string expression. Can be any valid expression as long as it resolves to a string.
     */
    public function split($string, $delimiter): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sqrt/
     *
     * @param mixed|self $expression
     */
    public function sqrt($expression): self
    {
        return $this->operator('$sqrt', $expression);
    }

    /**
     * Calculates the population standard deviation of the input values.
     *
     * The arguments can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevPop/
     *
     * @param mixed|self $expression
     * @param mixed|self ...$expressions Additional samples
     */
    public function stdDevPop($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$stdDevPop', $expression, ...$expressions);
    }

    /**
     * Calculates the sample standard deviation of the input values.
     *
     * The arguments can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/stdDevSamp/
     *
     * @param mixed|self $expression
     * @param mixed|self ...$expressions Additional samples
     */
    public function stdDevSamp($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$stdDevSamp', $expression, ...$expressions);
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
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function strcasecmp($expression1, $expression2): self
    {
        return $this->operator('$strcasecmp', [$expression1, $expression2]);
    }

    /**
     * Returns the number of UTF-8 encoded bytes in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenBytes/
     *
     * @param mixed|self $string
     */
    public function strLenBytes($string): self
    {
        return $this->operator('$strLenBytes', $string);
    }

    /**
     * Returns the number of UTF-8 code points in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenCP/
     *
     * @param mixed|self $string
     */
    public function strLenCP($string): self
    {
        return $this->operator('$strLenCP', $string);
    }

    /**
     * Returns a substring of a string, starting at a specified index position
     * and including the specified number of characters. The index is zero-based.
     *
     * The arguments can be any valid expression as long as long as the first argument resolves to a string, and the second and third arguments resolve to integers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substr/
     *
     * @param mixed|self $string
     * @param mixed|self $start
     * @param mixed|self $length
     */
    public function substr($string, $start, $length): self
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
     *
     * @param mixed|self $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $start  Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|self $count  can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer
     */
    public function substrBytes($string, $start, $count): self
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
     *
     * @param mixed|self $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|self $start  Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|self $count  can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer
     */
    public function substrCP($string, $start, $count): self
    {
        return $this->operator('$substrCP', [$string, $start, $count]);
    }

    /**
     * Subtracts two numbers to return the difference. The second argument is
     * subtracted from the first argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers and/or dates.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/subtract/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function subtract($expression1, $expression2): self
    {
        return $this->operator('$subtract', [$expression1, $expression2]);
    }

    /**
     * Calculates and returns the sum of all the numeric values that result from
     * applying a specified expression to each document in a group of documents
     * that share the same group by key. Ignores nun-numeric values.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sum/
     *
     * @param mixed|self $expression
     * @param mixed|self $expressions
     */
    public function sum($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$sum', $expression, ...$expressions);
    }

    /**
     * Converts value to a boolean.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toBool/
     *
     * @param mixed|self $expression
     */
    public function toBool($expression): self
    {
        return $this->operator('$toBool', $expression);
    }

    /**
     * Converts value to a Date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDate/
     *
     * @param mixed|self $expression
     */
    public function toDate($expression): self
    {
        return $this->operator('$toDate', $expression);
    }

    /**
     * Converts value to a Decimal128.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDecimal/
     *
     * @param mixed|self $expression
     */
    public function toDecimal($expression): self
    {
        return $this->operator('$toDecimal', $expression);
    }

    /**
     * Converts value to a double.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDouble/
     *
     * @param mixed|self $expression
     */
    public function toDouble($expression): self
    {
        return $this->operator('$toDouble', $expression);
    }

    /**
     * Converts value to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toInt/
     *
     * @param mixed|self $expression
     */
    public function toInt($expression): self
    {
        return $this->operator('$toInt', $expression);
    }

    /**
     * Converts value to a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLong/
     *
     * @param mixed|self $expression
     */
    public function toLong($expression): self
    {
        return $this->operator('$toLong', $expression);
    }

    /**
     * Converts a string to lowercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLower/
     *
     * @param mixed|self $expression
     */
    public function toLower($expression): self
    {
        return $this->operator('$toLower', $expression);
    }

    /**
     * Converts value to an ObjectId.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toObjectId/
     *
     * @param mixed|self $expression
     */
    public function toObjectId($expression): self
    {
        return $this->operator('$toObjectId', $expression);
    }

    /**
     * Returns the top element within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/top/
     *
     * @param mixed|self                $output
     * @param array<string, int|string> $sortBy
     */
    public function top($output, $sortBy): self
    {
        return $this->operator('$top', ['output' => $output, 'sortBy' => $sortBy]);
    }

    /**
     * Returns the n top elements within a group according to the specified sort order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/topN/
     *
     * @param mixed|self                $output
     * @param array<string, int|string> $sortBy
     * @param mixed|self                $n
     */
    public function topN($output, $sortBy, $n): self
    {
        return $this->operator('$topN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    /**
     * Converts value to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toString/
     *
     * @param mixed|self $expression
     */
    public function toString($expression): self
    {
        return $this->operator('$toString', $expression);
    }

    /**
     * Converts a string to uppercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toUpper/
     *
     * @param mixed|self $expression
     */
    public function toUpper($expression): self
    {
        return $this->operator('$toUpper', $expression);
    }

    /**
     * Truncates a number to its integer.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trunc/
     *
     * @param mixed|self $number
     */
    public function trunc($number): self
    {
        return $this->operator('$trunc', $number);
    }

    /**
     * Returns the incrementing ordinal from a timestamp as a long.
     *
     * @param mixed|self $expression
     */
    public function tsIncrement($expression): self
    {
        return $this->operator('$tsIncrement', $expression);
    }

    /**
     * Returns the seconds from a timestamp as a long.
     *
     * @param mixed|self $expression
     */
    public function tsSecond($expression): self
    {
        return $this->operator('$tsSecond', $expression);
    }

    /**
     * Returns a string that specifies the BSON type of the argument.
     *
     * The argument can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/type/
     *
     * @param mixed|self $expression
     */
    public function type($expression): self
    {
        return $this->operator('$type', $expression);
    }

    /**
     * Returns the week of the year for a date as a number between 0 and 53.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/week/
     *
     * @param mixed|self $expression
     */
    public function week($expression): self
    {
        return $this->operator('$week', $expression);
    }

    /**
     * Returns the year portion of a date.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/year/
     *
     * @param mixed|self $expression
     */
    public function year($expression): self
    {
        return $this->operator('$year', $expression);
    }

    /**
     * Transposes an array of input arrays so that the first element of the
     * output array would be an array containing, the first element of the first
     * input array, the first element of the second input array, etc.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/zip/
     *
     * @param mixed|self      $inputs           An array of expressions that resolve to arrays. The elements of these input arrays combine to form the arrays of the output array.
     * @param bool|null       $useLongestLength a boolean which specifies whether the length of the longest array determines the number of arrays in the output array
     * @param mixed|self|null $defaults         An array of default element values to use if the input arrays have different lengths. You must specify useLongestLength: true along with this field, or else $zip will return an error.
     */
    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): self
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
     * Wrapper for accumulator operators that exist in forms with one and multiple arguments
     *
     * @see Expr::operator()
     *
     * @param mixed|self ...$expressions
     */
    private function accumulatorOperator(string $operator, ...$expressions): self
    {
        if (count($expressions) === 1) {
            return $this->operator($operator, $expressions[0]);
        }

        return $this->operator($operator, $expressions);
    }

    /**
     * @param mixed|self $expression
     *
     * @return mixed
     */
    private function ensureArray($expression)
    {
        if (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        }

        if (is_array($expression)) {
            return array_map([$this, 'ensureArray'], $expression);
        }

        if ($expression instanceof self) {
            return $expression->getExpression();
        }

        // Convert PHP types to MongoDB types for everything else
        return Type::convertPHPToDatabaseValue($expression);
    }

    private function getDocumentPersister(): DocumentPersister
    {
        return $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);
    }

    /**
     * Defines an operator and value on the expression.
     *
     * If there is a current field, the operator will be set on it; otherwise,
     * the operator is set at the top level of the query.
     *
     * @param mixed|mixed[]|self $expression
     */
    private function operator(string $operator, $expression): self
    {
        if ($this->currentField) {
            $this->expr[$this->currentField][$operator] = $this->ensureArray($expression);
        } else {
            $this->expr[$operator] = $this->ensureArray($expression);
        }

        return $this;
    }

    /**
     * Adds one or more $or clause to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/or/
     *
     * @param array<string, mixed>|self $expression
     * @param array<string, mixed>|self ...$expressions
     */
    public function or($expression, ...$expressions): self
    {
        return $this->operator('$or', func_get_args());
    }

    /** @throws BadMethodCallException if there is no current switch operator. */
    private function requiresSwitchStatement(?string $method = null): void
    {
        $message = ($method ?: 'This method') . ' requires a valid switch statement (call switch() first).';

        if ($this->currentField) {
            if (! isset($this->expr[$this->currentField]['$switch'])) {
                throw new BadMethodCallException($message);
            }
        } elseif (! isset($this->expr['$switch'])) {
            throw new BadMethodCallException($message);
        }
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
     */
    public function then($expression): self
    {
        if (! is_array($this->switchBranch)) {
            throw new BadMethodCallException(static::class . '::then requires a valid case statement (call case() first).');
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

    /**
     * Converts an array into a single document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayToObject/
     *
     * @param mixed|self $array
     */
    public function arrayToObject($array): self
    {
        return $this->operator('$arrayToObject', $array);
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
     * @param mixed|self $object
     */
    public function objectToArray($object): self
    {
        return $this->operator('$objectToArray', $object);
    }

    /**
     * Provides regular expression (regex) pattern matching capability in
     * aggregation expressions.
     *
     * If a match is found, returns a document that contains information on the
     * first match. If a match is not found, returns null.
     *
     * @param mixed|self  $input
     * @param mixed|self  $regex
     * @param string|null $options
     */
    public function regexFind($input, $regex, $options = null): self
    {
        return $this->operator('$regexFind', $this->filterOptionalNullArguments([
            'input' => $input,
            'regex' => $regex,
            'options' => $options,
        ], ['options']));
    }

    /**
     * Provides regular expression (regex) pattern matching capability in
     * aggregation expressions.
     *
     * The operator returns an array of documents that contains information on
     * each match. If a match is not found, returns an empty array.
     *
     * @param mixed|self  $input
     * @param mixed|self  $regex
     * @param string|null $options
     */
    public function regexFindAll($input, $regex, $options = null): self
    {
        return $this->operator('$regexFindAll', $this->filterOptionalNullArguments([
            'input' => $input,
            'regex' => $regex,
            'options' => $options,
        ], ['options']));
    }

    /**
     * Performs a regular expression (regex) pattern matching and returns true
     * if a match exists.
     *
     * @param mixed|self  $input
     * @param mixed|self  $regex
     * @param string|null $options
     */
    public function regexMatch($input, $regex, $options = null): self
    {
        return $this->operator('$regexMatch', $this->filterOptionalNullArguments([
            'input' => $input,
            'regex' => $regex,
            'options' => $options,
        ], ['options']));
    }

    /**
     * Replaces all instances of a search string in an input string with a
     * replacement string.
     *
     * @param mixed|self $input
     * @param mixed|self $find
     * @param mixed|self $replacement
     */
    public function replaceAll($input, $find, $replacement): self
    {
        return $this->operator('$replaceAll', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    /**
     * Replaces the first instance of a search string in an input string with a
     * replacement string. If no occurrences are found, it evaluates to the
     * input string.
     *
     * @param mixed|self $input
     * @param mixed|self $find
     * @param mixed|self $replacement
     */
    public function replaceOne($input, $find, $replacement): self
    {
        return $this->operator('$replaceOne', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    /**
     * Rounds a number to a whole integer or to a specified decimal place.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/round/
     *
     * @param mixed|self      $number
     * @param mixed|self|null $place
     */
    public function round($number, $place = null): self
    {
        return $this->operator('$round', [$number, $place]);
    }

    /**
     * Removes whitespace characters, including null, or the specified characters from
     * the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trim/
     *
     * @param mixed|self $input
     * @param mixed|self $chars
     */
    public function trim($input, $chars = null): self
    {
        return $this->operator('$trim', [$input, $chars]);
    }

    /**
     * Removes whitespace characters, including null, or the specified characters from
     * the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ltrim/
     *
     * @param mixed|self $input
     * @param mixed|self $chars
     */
    public function ltrim($input, $chars = null): self
    {
        return $this->operator('$ltrim', [$input, $chars]);
    }

    /**
     * Removes whitespace characters, including null, or the specified characters from the end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rtrim/
     *
     * @param mixed|self $input
     * @param mixed|self $chars
     */
    public function rtrim($input, $chars = null): self
    {
        return $this->operator('$rtrim', [$input, $chars]);
    }

    /**
     * Returns the sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sin/
     *
     * @param mixed|self $expression
     */
    public function sin($expression): self
    {
        return $this->operator('$sin', $expression);
    }

    /**
     * Returns the cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cos/
     *
     * @param mixed|self $expression
     */
    public function cos($expression): self
    {
        return $this->operator('$cos', $expression);
    }

    /**
     * Returns the tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tan/
     *
     * @param mixed|self $expression
     */
    public function tan($expression): self
    {
        return $this->operator('$tan', $expression);
    }

    /**
     * Returns the inverse sin (arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asin/
     *
     * @param mixed|self $expression
     */
    public function asin($expression): self
    {
        return $this->operator('$asin', $expression);
    }

    /**
     * Returns the inverse cosine (arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acos/
     *
     * @param mixed|self $expression
     */
    public function acos($expression): self
    {
        return $this->operator('$acos', $expression);
    }

    /**
     * Returns the inverse tangent (arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan/
     *
     * @param mixed|self $expression
     */
    public function atan($expression): self
    {
        return $this->operator('$atan', $expression);
    }

    /**
     * Returns the inverse tangent (arc tangent) of y / x in radians, where y and x are the first and second values passed to the expression respectively.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan2/
     *
     * @param mixed|self $expression1
     * @param mixed|self $expression2
     */
    public function atan2($expression1, $expression2): self
    {
        return $this->operator('$atan2', [$expression1, $expression2]);
    }

    /**
     * Returns the inverse hyperbolic sine (hyperbolic arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asinh/
     *
     * @param mixed|self $expression
     */
    public function asinh($expression): self
    {
        return $this->operator('$asinh', $expression);
    }

    /**
     * Returns the inverse hyperbolic cosine (hyperbolic arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acosh/
     *
     * @param mixed|self $expression
     */
    public function acosh($expression): self
    {
        return $this->operator('$acosh', $expression);
    }

    /**
     * Returns the inverse hyperbolic tangent (hyperbolic arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atanh/
     *
     * @param mixed|self $expression
     */
    public function atanh($expression): self
    {
        return $this->operator('$atanh', $expression);
    }

    /**
     * Returns the hyperbolic sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sinh/
     *
     * @param mixed|self $expression
     */
    public function sinh($expression): self
    {
        return $this->operator('$sinh', $expression);
    }

    /**
     * Returns the hyperbolic cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cosh/
     *
     * @param mixed|self $expression
     */
    public function cosh($expression): self
    {
        return $this->operator('$cosh', $expression);
    }

    /**
     * Returns the hyperbolic tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tanh/
     *
     * @param mixed|self $expression
     */
    public function tanh($expression): self
    {
        return $this->operator('$tanh', $expression);
    }

    /**
     * Converts a value from degrees to radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/degreesToRadians/
     *
     * @param mixed|self $expression
     */
    public function degreesToRadians($expression): self
    {
        return $this->operator('$degreesToRadians', $expression);
    }

    /**
     * Converts a value from radians to degrees.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/radiansToDegrees/
     *
     * @param mixed|self $expression
     */
    public function radiansToDegrees($expression): self
    {
        return $this->operator('$radiansToDegrees', $expression);
    }

    /**
     * Converts a value to a specified type.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/convert/
     *
     * @param mixed|self      $input
     * @param mixed|self      $to
     * @param mixed|self|null $onError
     * @param mixed|self|null $onNull
     */
    public function convert($input, $to, $onError = null, $onNull = null): self
    {
        return $this->operator('$convert', $this->filterOptionalNullArguments([
            'input' => $input,
            'to' => $to,
            'onError' => $onError,
            'onNull' => $onNull,
        ], ['onError', 'onNull']));
    }

    /**
     * Returns boolean true if the specified expression resolves to an integer, decimal, double, or long.
     * Returns boolean false if the expression resolves to any other BSON type, null, or a missing field.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isNumber/
     *
     * @param mixed|self $expression
     */
    public function isNumber($expression): self
    {
        return $this->operator('$isNumber', $expression);
    }

    /**
     * @param array<string, mixed> $args
     * @param list<string>         $optionalArgNames
     *
     * @return array<string, mixed>
     */
    private function filterOptionalNullArguments(array $args, array $optionalArgNames): array
    {
        return array_filter(
            $args,
            /**
             * @param mixed $value
             * @param array-key $key
             */
            static fn ($value, $key): bool => ! in_array($key, $optionalArgNames) || $value !== null,
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
