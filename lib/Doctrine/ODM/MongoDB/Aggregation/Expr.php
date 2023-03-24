<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Aggregation\Operator\AccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArithmeticOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArrayOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\BooleanOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ComparisonOperators;
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
    ComparisonOperators,
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

    /** @return static */
    public function abs($number): self
    {
        return $this->operator('$abs', $number);
    }

    /** @return static */
    public function accumulator($init, $accumulate, $accumulateArgs, $merge, $initArgs = null, $finalize = null, $lang = 'js'): self
    {
        return $this->operator(
            '$accumulator',
            $this->filterOptionalNullArguments(
                [
                    'init' => $init,
                    'initArgs' => $initArgs,
                    'accumulate' => $accumulate,
                    'accumulateArgs' => $accumulateArgs,
                    'merge' => $merge,
                    'finalize' => $finalize,
                    'lang' => $lang,
                ],
                ['initArgs', 'finalize'],
            ),
        );
    }

    /** @return static */
    public function add($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$add', func_get_args());
    }

    /**
     * Adds one or more $and clauses to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/and/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     *
     * @return static
     */
    public function addAnd($expression, ...$expressions): self
    {
        if (! isset($this->expr['$and'])) {
            $this->expr['$and'] = [];
        }

        $this->expr['$and'] = array_merge($this->expr['$and'], array_map([$this, 'prepareArgument'], func_get_args()));

        return $this;
    }

    /**
     * Adds one or more $or clause to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/or/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     *
     * @return static
     */
    public function addOr($expression, ...$expressions): self
    {
        if (! isset($this->expr['$or'])) {
            $this->expr['$or'] = [];
        }

        $this->expr['$or'] = array_merge($this->expr['$or'], array_map([$this, 'prepareArgument'], func_get_args()));

        return $this;
    }

    /** @return static */
    public function addToSet($expression): self
    {
        return $this->operator('$addToSet', $expression);
    }

    /** @return static */
    public function allElementsTrue($expression): self
    {
        return $this->operator('$allElementsTrue', $expression);
    }

    /** @return static */
    public function and($expression, ...$expressions): self
    {
        return $this->operator('$and', func_get_args());
    }

    /** @return static */
    public function anyElementTrue($expression): self
    {
        return $this->operator('$anyElementTrue', $expression);
    }

    /** @return static */
    public function arrayElemAt($array, $index): self
    {
        return $this->operator('$arrayElemAt', func_get_args());
    }

    /** @return static */
    public function avg($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$avg', ...func_get_args());
    }

    /** @return static */
    public function binarySize($expression): self
    {
        return $this->operator('$binarySize', $expression);
    }

    /** @return static */
    public function bottom($output, $sortBy): self
    {
        return $this->operator('$bottom', ['output' => $output, 'sortBy' => $sortBy]);
    }

    /** @return static */
    public function bottomN($output, $sortBy, $n): self
    {
        return $this->operator('$bottomN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    /** @return static */
    public function bsonSize($expression): self
    {
        return $this->operator('$bsonSize', $expression);
    }

    /** @return static */
    public function case($expression): self
    {
        $this->requiresSwitchStatement(static::class . '::case');

        $this->switchBranch = ['case' => $expression];

        return $this;
    }

    /** @return static */
    public function ceil($number): self
    {
        return $this->operator('$ceil', $number);
    }

    /** @return static */
    public function cmp($expression1, $expression2): self
    {
        return $this->operator('$cmp', func_get_args());
    }

    /** @return static */
    public function concat($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$concat', func_get_args());
    }

    /** @return static */
    public function concatArrays($array1, $array2, ...$arrays): self
    {
        return $this->operator('$concatArrays', func_get_args());
    }

    /** @return static */
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

    /** @return static */
    public function countDocuments(): self
    {
        return $this->operator('$count', []);
    }

    /** @return static */
    public function dateAdd($startDate, $unit, $amount, $timezone = null): self
    {
        return $this->operator(
            '$dateAdd',
            $this->filterOptionalNullArguments(
                [
                    'startDate' => $startDate,
                    'unit' => $unit,
                    'amount' => $amount,
                    'timezone' => $timezone,
                ],
                ['timezone'],
            ),
        );
    }

    /** @return static */
    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): self
    {
        return $this->operator(
            '$dateDiff',
            $this->filterOptionalNullArguments(
                [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'unit' => $unit,
                    'timezone' => $timezone,
                    'startOfWeek' => $startOfWeek,
                ],
                ['timezone', 'startOfWeek'],
            ),
        );
    }

    /** @return static */
    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): self
    {
        return $this->operator(
            '$dateFromParts',
            $this->filterOptionalNullArguments(
                [
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
                ],
                [
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
                ],
            ),
        );
    }

    /** @return static */
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
                ['format', 'timezone', 'onError', 'onNull'],
            ),
        );
    }

    /** @return static */
    public function dateSubtract($startDate, $unit, $amount, $timezone = null): self
    {
        return $this->operator(
            '$dateSubtract',
            $this->filterOptionalNullArguments(
                [
                    'startDate' => $startDate,
                    'unit' => $unit,
                    'amount' => $amount,
                    'timezone' => $timezone,
                ],
                ['timezone'],
            ),
        );
    }

    /** @return static */
    public function dateToParts($date, $timezone = null, $iso8601 = null): self
    {
        return $this->operator(
            '$dateToParts',
            $this->filterOptionalNullArguments(
                [
                    'date' => $date,
                    'timezone' => $timezone,
                    'iso8601' => $iso8601,
                ],
                ['timezone', 'iso8601'],
            ),
        );
    }

    /** @return static */
    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): self
    {
        return $this->operator(
            '$dateToString',
            $this->filterOptionalNullArguments(
                [
                    'date' => $expression,
                    'format' => $format,
                    'timezone' => $timezone,
                    'onNull' => $onNull,
                ],
                ['timezone', 'onNull'],
            ),
        );
    }

    /** @return static */
    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): self
    {
        return $this->operator(
            '$dateTrunc',
            $this->filterOptionalNullArguments(
                [
                    'date' => $date,
                    'unit' => $unit,
                    'binSize' => $binSize,
                    'timezone' => $timezone,
                    'startOfWeek' => $startOfWeek,
                ],
                ['binSize', 'timezone', 'startOfWeek'],
            ),
        );
    }

    /** @return static */
    public function dayOfMonth($expression): self
    {
        return $this->operator('$dayOfMonth', $expression);
    }

    /** @return static */
    public function dayOfWeek($expression): self
    {
        return $this->operator('$dayOfWeek', $expression);
    }

    /** @return static */
    public function dayOfYear($expression): self
    {
        return $this->operator('$dayOfYear', $expression);
    }

    /** @return static */
    public function default($expression): self
    {
        $this->requiresSwitchStatement(static::class . '::default');

        if ($this->currentField) {
            $this->expr[$this->currentField]['$switch']['default'] = $this->prepareArgument($expression);
        } else {
            $this->expr['$switch']['default'] = $this->prepareArgument($expression);
        }

        return $this;
    }

    /** @return static */
    public function divide($expression1, $expression2): self
    {
        return $this->operator('$divide', func_get_args());
    }

    /** @return static */
    public function eq($expression1, $expression2): self
    {
        return $this->operator('$eq', func_get_args());
    }

    /** @return static */
    public function exp($exponent): self
    {
        return $this->operator('$exp', $exponent);
    }

    /**
     * Returns a new expression object.
     */
    public function expr(): static
    {
        return new static($this->dm, $this->class);
    }

    /** @return static */
    public function expression($value): self
    {
        if (! $this->currentField) {
            throw new LogicException(sprintf('%s requires setting a current field using field().', __METHOD__));
        }

        $this->expr[$this->currentField] = $this->prepareArgument($value);

        return $this;
    }

    /**
     * Set the current field for building the expression.
     */
    public function field(string $fieldName): static
    {
        $fieldName          = $this->getDocumentPersister()->prepareFieldName($fieldName);
        $this->currentField = $fieldName;

        return $this;
    }

    /** @return static */
    public function filter($input, $as, $cond): self
    {
        return $this->operator('$filter', ['input' => $input, 'as' => $as, 'cond' => $cond]);
    }

    /** @return static */
    public function first($expression): self
    {
        return $this->operator('$first', $expression);
    }

    /** @return static */
    public function firstN($expression, $n): self
    {
        return $this->operator('$firstN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /** @return static */
    public function function($body, $args, $lang = 'js'): self
    {
        return $this->operator('$function', ['body' => $body, 'args' => $args, 'lang' => $lang]);
    }

    /** @return static */
    public function floor($number): self
    {
        return $this->operator('$floor', $number);
    }

    /** @return array<string, mixed> */
    public function getExpression(): array
    {
        return $this->expr;
    }

    /** @return static */
    public function getField($field, $input = null): self
    {
        return $this->operator(
            '$getField',
            $this->filterOptionalNullArguments(
                [
                    'field' => $field,
                    'input' => $input,
                ],
                ['input'],
            ),
        );
    }

    /** @return static */
    public function gt($expression1, $expression2): self
    {
        return $this->operator('$gt', func_get_args());
    }

    /** @return static */
    public function gte($expression1, $expression2): self
    {
        return $this->operator('$gte', func_get_args());
    }

    /** @return static */
    public function hour($expression): self
    {
        return $this->operator('$hour', $expression);
    }

    /** @return static */
    public function ifNull($expression, $replacementExpression): self
    {
        return $this->operator('$ifNull', func_get_args());
    }

    /** @return static */
    public function in($expression, $arrayExpression): self
    {
        return $this->operator('$in', func_get_args());
    }

    /** @return static */
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

    /** @return static */
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

    /** @return static */
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

    /** @return static */
    public function isArray($expression): self
    {
        return $this->operator('$isArray', $expression);
    }

    /** @return static */
    public function isoDayOfWeek($expression): self
    {
        return $this->operator('$isoDayOfWeek', $expression);
    }

    /** @return static */
    public function isoWeek($expression): self
    {
        return $this->operator('$isoWeek', $expression);
    }

    /** @return static */
    public function isoWeekYear($expression): self
    {
        return $this->operator('$isoWeekYear', $expression);
    }

    /** @return static */
    public function last($expression): self
    {
        return $this->operator('$last', $expression);
    }

    /** @return static */
    public function lastN($expression, $n): self
    {
        return $this->operator('$lastN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /** @return static */
    public function let($vars, $in): self
    {
        return $this->operator('$let', ['vars' => $vars, 'in' => $in]);
    }

    /** @return static */
    public function literal($value): self
    {
        return $this->operator('$literal', $value);
    }

    /** @return static */
    public function ln($number): self
    {
        return $this->operator('$ln', $number);
    }

    /** @return static */
    public function log($number, $base): self
    {
        return $this->operator('$log', func_get_args());
    }

    /** @return static */
    public function log10($number): self
    {
        return $this->operator('$log10', $number);
    }

    /** @return static */
    public function lt($expression1, $expression2): self
    {
        return $this->operator('$lt', func_get_args());
    }

    /** @return static */
    public function lte($expression1, $expression2): self
    {
        return $this->operator('$lte', func_get_args());
    }

    /** @return static */
    public function map($input, $as, $in): self
    {
        return $this->operator('$map', ['input' => $input, 'as' => $as, 'in' => $in]);
    }

    /** @return static */
    public function max($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$max', ...func_get_args());
    }

    /** @return static */
    public function maxN($expression, $n): self
    {
        return $this->operator('$maxN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /** @return static */
    public function mergeObjects($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$mergeObjects', ...func_get_args());
    }

    /** @return static */
    public function meta($metaDataKeyword): self
    {
        return $this->operator('$meta', $metaDataKeyword);
    }

    /** @return static */
    public function millisecond($expression): self
    {
        return $this->operator('$millisecond', $expression);
    }

    /** @return static */
    public function min($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$min', ...func_get_args());
    }

    /** @return static */
    public function minN($expression, $n): self
    {
        return $this->operator('$minN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    /** @return static */
    public function minute($expression): self
    {
        return $this->operator('$minute', $expression);
    }

    /** @return static */
    public function mod($expression1, $expression2): self
    {
        return $this->operator('$mod', func_get_args());
    }

    /** @return static */
    public function month($expression): self
    {
        return $this->operator('$month', $expression);
    }

    /** @return static */
    public function multiply($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$multiply', func_get_args());
    }

    /** @return static */
    public function ne($expression1, $expression2): self
    {
        return $this->operator('$ne', func_get_args());
    }

    /** @return static */
    public function not($expression): self
    {
        return $this->operator('$not', $expression);
    }

    /** @return static */
    public function pow($number, $exponent): self
    {
        return $this->operator('$pow', func_get_args());
    }

    /** @return static */
    public function push($expression): self
    {
        return $this->operator('$push', $expression);
    }

    /** @return static */
    public function rand(): self
    {
        return $this->operator('$rand', []);
    }

    /** @return static */
    public function range($start, $end, $step = 1): self
    {
        return $this->operator('$range', func_get_args());
    }

    /** @return static */
    public function reduce($input, $initialValue, $in): self
    {
        return $this->operator('$reduce', ['input' => $input, 'initialValue' => $initialValue, 'in' => $in]);
    }

    /** @return static */
    public function reverseArray($expression): self
    {
        return $this->operator('$reverseArray', $expression);
    }

    /** @return static */
    public function sampleRate(float $rate): self
    {
        return $this->operator('$sampleRate', $rate);
    }

    /** @return static */
    public function second($expression): self
    {
        return $this->operator('$second', $expression);
    }

    /** @return static */
    public function setDifference($expression1, $expression2): self
    {
        return $this->operator('$setDifference', func_get_args());
    }

    /** @return static */
    public function setEquals($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$setEquals', func_get_args());
    }

    /** @return static */
    public function setField($field, $input, $value): self
    {
        return $this->operator('$setField', ['field' => $field, 'input' => $input, 'value' => $value]);
    }

    /** @return static */
    public function setIntersection($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$setIntersection', func_get_args());
    }

    /** @return static */
    public function setIsSubset($expression1, $expression2): self
    {
        return $this->operator('$setIsSubset', func_get_args());
    }

    /** @return static */
    public function setUnion($expression1, $expression2, ...$expressions): self
    {
        return $this->operator('$setUnion', func_get_args());
    }

    /** @return static */
    public function size($expression): self
    {
        return $this->operator('$size', $expression);
    }

    /** @return static */
    public function slice($array, $n, $position = null): self
    {
        if ($position === null) {
            return $this->operator('$slice', func_get_args());
        }

        return $this->operator('$slice', func_get_args());
    }

    /** @return static */
    public function sortArray($input, $sortBy): self
    {
        return $this->operator('$sortArray', [
            'input' => $input,
            'sortBy' => $sortBy,
        ]);
    }

    /** @return static */
    public function split($string, $delimiter): self
    {
        return $this->operator('$split', func_get_args());
    }

    /** @return static */
    public function sqrt($expression): self
    {
        return $this->operator('$sqrt', $expression);
    }

    /** @return static */
    public function stdDevPop($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$stdDevPop', ...func_get_args());
    }

    /** @return static */
    public function stdDevSamp($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$stdDevSamp', ...func_get_args());
    }

    /** @return static */
    public function strcasecmp($expression1, $expression2): self
    {
        return $this->operator('$strcasecmp', func_get_args());
    }

    /** @return static */
    public function strLenBytes($string): self
    {
        return $this->operator('$strLenBytes', $string);
    }

    /** @return static */
    public function strLenCP($string): self
    {
        return $this->operator('$strLenCP', $string);
    }

    /** @return static */
    public function substr($string, $start, $length): self
    {
        return $this->operator('$substr', func_get_args());
    }

    /** @return static */
    public function substrBytes($string, $start, $count): self
    {
        return $this->operator('$substrBytes', func_get_args());
    }

    /** @return static */
    public function substrCP($string, $start, $count): self
    {
        return $this->operator('$substrCP', func_get_args());
    }

    /** @return static */
    public function subtract($expression1, $expression2): self
    {
        return $this->operator('$subtract', func_get_args());
    }

    /** @return static */
    public function sum($expression, ...$expressions): self
    {
        return $this->accumulatorOperator('$sum', ...func_get_args());
    }

    /** @return static */
    public function toBool($expression): self
    {
        return $this->operator('$toBool', $expression);
    }

    /** @return static */
    public function toDate($expression): self
    {
        return $this->operator('$toDate', $expression);
    }

    /** @return static */
    public function toDecimal($expression): self
    {
        return $this->operator('$toDecimal', $expression);
    }

    /** @return static */
    public function toDouble($expression): self
    {
        return $this->operator('$toDouble', $expression);
    }

    /** @return static */
    public function toInt($expression): self
    {
        return $this->operator('$toInt', $expression);
    }

    /** @return static */
    public function toLong($expression): self
    {
        return $this->operator('$toLong', $expression);
    }

    /** @return static */
    public function toLower($expression): self
    {
        return $this->operator('$toLower', $expression);
    }

    /** @return static */
    public function toObjectId($expression): self
    {
        return $this->operator('$toObjectId', $expression);
    }

    /** @return static */
    public function top($output, $sortBy): self
    {
        return $this->operator('$top', ['output' => $output, 'sortBy' => $sortBy]);
    }

    /** @return static */
    public function topN($output, $sortBy, $n): self
    {
        return $this->operator('$topN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    /** @return static */
    public function toString($expression): self
    {
        return $this->operator('$toString', $expression);
    }

    /** @return static */
    public function toUpper($expression): self
    {
        return $this->operator('$toUpper', $expression);
    }

    /** @return static */
    public function trunc($number): self
    {
        return $this->operator('$trunc', $number);
    }

    /** @return static */
    public function tsIncrement($expression): self
    {
        return $this->operator('$tsIncrement', $expression);
    }

    /** @return static */
    public function tsSecond($expression): self
    {
        return $this->operator('$tsSecond', $expression);
    }

    /** @return static */
    public function type($expression): self
    {
        return $this->operator('$type', $expression);
    }

    /** @return static */
    public function week($expression): self
    {
        return $this->operator('$week', $expression);
    }

    /** @return static */
    public function year($expression): self
    {
        return $this->operator('$year', $expression);
    }

    /** @return static */
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
     *
     * @return static
     */
    private function accumulatorOperator(string $operator, ...$expressions): self
    {
        if (count($expressions) === 1) {
            return $this->operator($operator, $expressions[0]);
        }

        return $this->operator($operator, $expressions);
    }

    /**
     * Prepares an argument for an operator. It follows these ruls:
     * - If the argument is a string starting with a $, it is considered a field name and is transformed according to mapping information.
     * - If the argument is an array, it is recursively prepared.
     * - If the argument is an Expr instance, its expression is returned.
     * - Otherwise, the argument is converted to a MongoDB type according to the ODM type information.
     *
     * @param mixed|self $expression
     *
     * @return mixed
     */
    private function prepareArgument($expression)
    {
        if (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        }

        if (is_array($expression)) {
            return array_map([$this, 'prepareArgument'], $expression);
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
     *
     * @return static
     */
    private function operator(string $operator, $expression): self
    {
        if ($this->currentField) {
            $this->expr[$this->currentField][$operator] = $this->prepareArgument($expression);
        } else {
            $this->expr[$operator] = $this->prepareArgument($expression);
        }

        return $this;
    }

    /** @return static */
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

    /** @return static */
    public function switch(): self
    {
        $this->operator('$switch', []);

        return $this;
    }

    /** @return static */
    public function then($expression): self
    {
        if (! is_array($this->switchBranch)) {
            throw new BadMethodCallException(static::class . '::then requires a valid case statement (call case() first).');
        }

        $this->switchBranch['then'] = $expression;

        if ($this->currentField) {
            $this->expr[$this->currentField]['$switch']['branches'][] = $this->prepareArgument($this->switchBranch);
        } else {
            $this->expr['$switch']['branches'][] = $this->prepareArgument($this->switchBranch);
        }

        $this->switchBranch = null;

        return $this;
    }

    /** @return static */
    public function arrayToObject($array): self
    {
        return $this->operator('$arrayToObject', $array);
    }

    /** @return static */
    public function objectToArray($object): self
    {
        return $this->operator('$objectToArray', $object);
    }

    /** @return static */
    public function regexFind($input, $regex, $options = null): self
    {
        return $this->operator(
            '$regexFind',
            $this->filterOptionalNullArguments(
                [
                    'input' => $input,
                    'regex' => $regex,
                    'options' => $options,
                ],
                ['options'],
            ),
        );
    }

    /** @return static */
    public function regexFindAll($input, $regex, $options = null): self
    {
        return $this->operator(
            '$regexFindAll',
            $this->filterOptionalNullArguments(
                [
                    'input' => $input,
                    'regex' => $regex,
                    'options' => $options,
                ],
                ['options'],
            ),
        );
    }

    /** @return static */
    public function regexMatch($input, $regex, $options = null): self
    {
        return $this->operator(
            '$regexMatch',
            $this->filterOptionalNullArguments(
                [
                    'input' => $input,
                    'regex' => $regex,
                    'options' => $options,
                ],
                ['options'],
            ),
        );
    }

    /** @return static */
    public function replaceAll($input, $find, $replacement): self
    {
        return $this->operator('$replaceAll', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    /** @return static */
    public function replaceOne($input, $find, $replacement): self
    {
        return $this->operator('$replaceOne', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    /** @return static */
    public function round($number, $place = null): self
    {
        return $this->operator('$round', func_get_args());
    }

    /** @return static */
    public function trim($input, $chars = null): self
    {
        return $this->operator('$trim', func_get_args());
    }

    /** @return static */
    public function ltrim($input, $chars = null): self
    {
        return $this->operator('$ltrim', func_get_args());
    }

    /** @return static */
    public function rtrim($input, $chars = null): self
    {
        return $this->operator('$rtrim', func_get_args());
    }

    /** @return static */
    public function sin($expression): self
    {
        return $this->operator('$sin', $expression);
    }

    /** @return static */
    public function cos($expression): self
    {
        return $this->operator('$cos', $expression);
    }

    /** @return static */
    public function tan($expression): self
    {
        return $this->operator('$tan', $expression);
    }

    /** @return static */
    public function asin($expression): self
    {
        return $this->operator('$asin', $expression);
    }

    /** @return static */
    public function acos($expression): self
    {
        return $this->operator('$acos', $expression);
    }

    /** @return static */
    public function atan($expression): self
    {
        return $this->operator('$atan', $expression);
    }

    /** @return static */
    public function atan2($expression1, $expression2): self
    {
        return $this->operator('$atan2', func_get_args());
    }

    /** @return static */
    public function asinh($expression): self
    {
        return $this->operator('$asinh', $expression);
    }

    /** @return static */
    public function acosh($expression): self
    {
        return $this->operator('$acosh', $expression);
    }

    /** @return static */
    public function atanh($expression): self
    {
        return $this->operator('$atanh', $expression);
    }

    /** @return static */
    public function sinh($expression): self
    {
        return $this->operator('$sinh', $expression);
    }

    /** @return static */
    public function cosh($expression): self
    {
        return $this->operator('$cosh', $expression);
    }

    /** @return static */
    public function tanh($expression): self
    {
        return $this->operator('$tanh', $expression);
    }

    /** @return static */
    public function degreesToRadians($expression): self
    {
        return $this->operator('$degreesToRadians', $expression);
    }

    /** @return static */
    public function radiansToDegrees($expression): self
    {
        return $this->operator('$radiansToDegrees', $expression);
    }

    /** @return static */
    public function convert($input, $to, $onError = null, $onNull = null): self
    {
        return $this->operator(
            '$convert',
            $this->filterOptionalNullArguments(
                [
                    'input' => $input,
                    'to' => $to,
                    'onError' => $onError,
                    'onNull' => $onNull,
                ],
                ['onError', 'onNull'],
            ),
        );
    }

    /** @return static */
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
            static fn ($value, $key): bool => $value !== null || ! in_array($key, $optionalArgNames),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
