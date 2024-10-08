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
use Doctrine\ODM\MongoDB\Aggregation\Operator\WindowOperators;
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
 * @phpstan-type OperatorExpression array<string, mixed>|object
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
    TypeOperators,
    WindowOperators
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

    public function abs($number): static
    {
        return $this->operator('$abs', $number);
    }

    public function accumulator($init, $accumulate, $accumulateArgs, $merge, $initArgs = null, $finalize = null, $lang = 'js'): static
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

    public function add($expression1, $expression2, ...$expressions): static
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
     */
    public function addAnd($expression, ...$expressions): static
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
     */
    public function addOr($expression, ...$expressions): static
    {
        if (! isset($this->expr['$or'])) {
            $this->expr['$or'] = [];
        }

        $this->expr['$or'] = array_merge($this->expr['$or'], array_map([$this, 'prepareArgument'], func_get_args()));

        return $this;
    }

    public function addToSet($expression): static
    {
        return $this->operator('$addToSet', $expression);
    }

    public function allElementsTrue($expression): static
    {
        return $this->operator('$allElementsTrue', $expression);
    }

    public function and($expression, ...$expressions): static
    {
        return $this->operator('$and', func_get_args());
    }

    public function anyElementTrue($expression): static
    {
        return $this->operator('$anyElementTrue', $expression);
    }

    public function arrayElemAt($array, $index): static
    {
        return $this->operator('$arrayElemAt', func_get_args());
    }

    public function avg($expression, ...$expressions): static
    {
        return $this->accumulatorOperator('$avg', ...func_get_args());
    }

    public function binarySize($expression): static
    {
        return $this->operator('$binarySize', $expression);
    }

    public function bottom($output, $sortBy): static
    {
        return $this->operator('$bottom', ['output' => $output, 'sortBy' => $sortBy]);
    }

    public function bottomN($output, $sortBy, $n): static
    {
        return $this->operator('$bottomN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    public function bsonSize($expression): static
    {
        return $this->operator('$bsonSize', $expression);
    }

    public function case($expression): static
    {
        $this->requiresSwitchStatement(static::class . '::case');

        $this->switchBranch = ['case' => $expression];

        return $this;
    }

    public function ceil($number): static
    {
        return $this->operator('$ceil', $number);
    }

    public function cmp($expression1, $expression2): static
    {
        return $this->operator('$cmp', func_get_args());
    }

    public function concat($expression1, $expression2, ...$expressions): static
    {
        return $this->operator('$concat', func_get_args());
    }

    public function concatArrays($array1, $array2, ...$arrays): static
    {
        return $this->operator('$concatArrays', func_get_args());
    }

    public function cond($if, $then, $else): static
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

    public function countDocuments(): static
    {
        return $this->operator('$count', []);
    }

    public function covariancePop($expression1, $expression2): static
    {
        return $this->operator('$covariancePop', func_get_args());
    }

    public function covarianceSamp($expression1, $expression2): static
    {
        return $this->operator('$covarianceSamp', func_get_args());
    }

    public function dateAdd($startDate, $unit, $amount, $timezone = null): static
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

    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): static
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

    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): static
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

    public function dateFromString($dateString, $format = null, $timezone = null, $onError = null, $onNull = null): static
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

    public function dateSubtract($startDate, $unit, $amount, $timezone = null): static
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

    public function dateToParts($date, $timezone = null, $iso8601 = null): static
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

    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): static
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

    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): static
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

    public function dayOfMonth($expression): static
    {
        return $this->operator('$dayOfMonth', $expression);
    }

    public function dayOfWeek($expression): static
    {
        return $this->operator('$dayOfWeek', $expression);
    }

    public function dayOfYear($expression): static
    {
        return $this->operator('$dayOfYear', $expression);
    }

    public function default($expression): static
    {
        $this->requiresSwitchStatement(static::class . '::default');

        if ($this->currentField) {
            $this->expr[$this->currentField]['$switch']['default'] = $this->prepareArgument($expression);
        } else {
            $this->expr['$switch']['default'] = $this->prepareArgument($expression);
        }

        return $this;
    }

    public function denseRank(): static
    {
        return $this->operator('$denseRank', []);
    }

    public function derivative($input, string $unit): static
    {
        return $this->operator('$derivative', ['input' => $input, 'unit' => $unit]);
    }

    public function divide($expression1, $expression2): static
    {
        return $this->operator('$divide', func_get_args());
    }

    public function documentNumber(): static
    {
        return $this->operator('$documentNumber', []);
    }

    public function eq($expression1, $expression2): static
    {
        return $this->operator('$eq', func_get_args());
    }

    public function exp($exponent): static
    {
        return $this->operator('$exp', $exponent);
    }

    public function expMovingAvg($input, ?int $n = null, ?float $alpha = null): static
    {
        return $this->operator(
            '$expMovingAvg',
            $this->filterOptionalNullArguments(
                [
                    'input' => $input,
                    'N' => $n,
                    'alpha' => $alpha,
                ],
                ['N', 'alpha'],
            ),
        );
    }

    /**
     * Returns a new expression object.
     */
    public function expr(): static
    {
        return new static($this->dm, $this->class);
    }

    public function expression($value): static
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

    public function filter($input, $as, $cond): static
    {
        return $this->operator('$filter', ['input' => $input, 'as' => $as, 'cond' => $cond]);
    }

    public function first($expression): static
    {
        return $this->operator('$first', $expression);
    }

    public function firstN($expression, $n): static
    {
        return $this->operator('$firstN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function function($body, $args, $lang = 'js'): static
    {
        return $this->operator('$function', ['body' => $body, 'args' => $args, 'lang' => $lang]);
    }

    public function floor($number): static
    {
        return $this->operator('$floor', $number);
    }

    /** @return array<string, mixed> */
    public function getExpression(): array
    {
        return $this->expr;
    }

    public function getField($field, $input = null): static
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

    public function gt($expression1, $expression2): static
    {
        return $this->operator('$gt', func_get_args());
    }

    public function gte($expression1, $expression2): static
    {
        return $this->operator('$gte', func_get_args());
    }

    public function hour($expression): static
    {
        return $this->operator('$hour', $expression);
    }

    public function ifNull($expression, $replacementExpression): static
    {
        return $this->operator('$ifNull', func_get_args());
    }

    public function in($expression, $arrayExpression): static
    {
        return $this->operator('$in', func_get_args());
    }

    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null): static
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

    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): static
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

    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): static
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

    public function integral($input, string $unit): static
    {
        return $this->operator('$integral', ['input' => $input, 'unit' => $unit]);
    }

    public function isArray($expression): static
    {
        return $this->operator('$isArray', $expression);
    }

    public function isoDayOfWeek($expression): static
    {
        return $this->operator('$isoDayOfWeek', $expression);
    }

    public function isoWeek($expression): static
    {
        return $this->operator('$isoWeek', $expression);
    }

    public function isoWeekYear($expression): static
    {
        return $this->operator('$isoWeekYear', $expression);
    }

    public function last($expression): static
    {
        return $this->operator('$last', $expression);
    }

    public function lastN($expression, $n): static
    {
        return $this->operator('$lastN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function let($vars, $in): static
    {
        return $this->operator('$let', ['vars' => $vars, 'in' => $in]);
    }

    public function linearFill($expression): static
    {
        return $this->operator('$linearFill', $expression);
    }

    public function literal($value): static
    {
        return $this->operator('$literal', $value);
    }

    public function ln($number): static
    {
        return $this->operator('$ln', $number);
    }

    public function locf($expression): static
    {
        return $this->operator('$locf', $expression);
    }

    public function log($number, $base): static
    {
        return $this->operator('$log', func_get_args());
    }

    public function log10($number): static
    {
        return $this->operator('$log10', $number);
    }

    public function lt($expression1, $expression2): static
    {
        return $this->operator('$lt', func_get_args());
    }

    public function lte($expression1, $expression2): static
    {
        return $this->operator('$lte', func_get_args());
    }

    public function map($input, $as, $in): static
    {
        return $this->operator('$map', ['input' => $input, 'as' => $as, 'in' => $in]);
    }

    public function max($expression, ...$expressions): static
    {
        return $this->accumulatorOperator('$max', ...func_get_args());
    }

    public function maxN($expression, $n): static
    {
        return $this->operator('$maxN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function mergeObjects($expression, ...$expressions): static
    {
        return $this->accumulatorOperator('$mergeObjects', ...func_get_args());
    }

    public function meta($metaDataKeyword): static
    {
        return $this->operator('$meta', $metaDataKeyword);
    }

    public function millisecond($expression): static
    {
        return $this->operator('$millisecond', $expression);
    }

    public function min($expression, ...$expressions): static
    {
        return $this->accumulatorOperator('$min', ...func_get_args());
    }

    public function minN($expression, $n): static
    {
        return $this->operator('$minN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function minute($expression): static
    {
        return $this->operator('$minute', $expression);
    }

    public function mod($expression1, $expression2): static
    {
        return $this->operator('$mod', func_get_args());
    }

    public function month($expression): static
    {
        return $this->operator('$month', $expression);
    }

    public function multiply($expression1, $expression2, ...$expressions): static
    {
        return $this->operator('$multiply', func_get_args());
    }

    public function ne($expression1, $expression2): static
    {
        return $this->operator('$ne', func_get_args());
    }

    public function not($expression): static
    {
        return $this->operator('$not', $expression);
    }

    public function pow($number, $exponent): static
    {
        return $this->operator('$pow', func_get_args());
    }

    public function push($expression): static
    {
        return $this->operator('$push', $expression);
    }

    public function rand(): static
    {
        return $this->operator('$rand', []);
    }

    public function range($start, $end, $step = null): static
    {
        return $this->operator('$range', func_get_args());
    }

    public function rank(): static
    {
        return $this->operator('$rank', []);
    }

    public function reduce($input, $initialValue, $in): static
    {
        return $this->operator('$reduce', ['input' => $input, 'initialValue' => $initialValue, 'in' => $in]);
    }

    public function reverseArray($expression): static
    {
        return $this->operator('$reverseArray', $expression);
    }

    public function sampleRate(float $rate): static
    {
        return $this->operator('$sampleRate', $rate);
    }

    public function second($expression): static
    {
        return $this->operator('$second', $expression);
    }

    public function setDifference($expression1, $expression2): static
    {
        return $this->operator('$setDifference', func_get_args());
    }

    public function setEquals($expression1, $expression2, ...$expressions): static
    {
        return $this->operator('$setEquals', func_get_args());
    }

    public function setField($field, $input, $value): static
    {
        return $this->operator('$setField', ['field' => $field, 'input' => $input, 'value' => $value]);
    }

    public function setIntersection($expression1, $expression2, ...$expressions): static
    {
        return $this->operator('$setIntersection', func_get_args());
    }

    public function setIsSubset($expression1, $expression2): static
    {
        return $this->operator('$setIsSubset', func_get_args());
    }

    public function setUnion($expression1, $expression2, ...$expressions): static
    {
        return $this->operator('$setUnion', func_get_args());
    }

    public function shift($output, int $by, $default = null): static
    {
        return $this->operator(
            '$shift',
            $this->filterOptionalNullArguments(
                [
                    'output' => $output,
                    'by' => $by,
                    'default' => $default,
                ],
                ['default'],
            ),
        );
    }

    public function size($expression): static
    {
        return $this->operator('$size', $expression);
    }

    public function slice($array, $n, $position = null): static
    {
        // With two args provided, the order of parameters is <array>, <n>.
        // With three args provided, the order of parameters is <array>,
        // <position>, <n>.
        if ($position !== null) {
            $args = [$array, $position, $n];
        } else {
            $args = [$array, $n];
        }

        return $this->operator('$slice', $args);
    }

    public function sortArray($input, $sortBy): static
    {
        return $this->operator('$sortArray', [
            'input' => $input,
            'sortBy' => $sortBy,
        ]);
    }

    public function split($string, $delimiter): static
    {
        return $this->operator('$split', func_get_args());
    }

    public function sqrt($expression): static
    {
        return $this->operator('$sqrt', $expression);
    }

    public function stdDevPop($expression, ...$expressions): static
    {
        return $this->accumulatorOperator('$stdDevPop', ...func_get_args());
    }

    public function stdDevSamp($expression, ...$expressions): static
    {
        return $this->accumulatorOperator('$stdDevSamp', ...func_get_args());
    }

    public function strcasecmp($expression1, $expression2): static
    {
        return $this->operator('$strcasecmp', func_get_args());
    }

    public function strLenBytes($string): static
    {
        return $this->operator('$strLenBytes', $string);
    }

    public function strLenCP($string): static
    {
        return $this->operator('$strLenCP', $string);
    }

    public function substr($string, $start, $length): static
    {
        return $this->operator('$substr', func_get_args());
    }

    public function substrBytes($string, $start, $count): static
    {
        return $this->operator('$substrBytes', func_get_args());
    }

    public function substrCP($string, $start, $count): static
    {
        return $this->operator('$substrCP', func_get_args());
    }

    public function subtract($expression1, $expression2): static
    {
        return $this->operator('$subtract', func_get_args());
    }

    public function sum($expression, ...$expressions): static
    {
        return $this->accumulatorOperator('$sum', ...func_get_args());
    }

    public function toBool($expression): static
    {
        return $this->operator('$toBool', $expression);
    }

    public function toDate($expression): static
    {
        return $this->operator('$toDate', $expression);
    }

    public function toDecimal($expression): static
    {
        return $this->operator('$toDecimal', $expression);
    }

    public function toDouble($expression): static
    {
        return $this->operator('$toDouble', $expression);
    }

    public function toInt($expression): static
    {
        return $this->operator('$toInt', $expression);
    }

    public function toLong($expression): static
    {
        return $this->operator('$toLong', $expression);
    }

    public function toLower($expression): static
    {
        return $this->operator('$toLower', $expression);
    }

    public function toObjectId($expression): static
    {
        return $this->operator('$toObjectId', $expression);
    }

    public function top($output, $sortBy): static
    {
        return $this->operator('$top', ['output' => $output, 'sortBy' => $sortBy]);
    }

    public function topN($output, $sortBy, $n): static
    {
        return $this->operator('$topN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    public function toString($expression): static
    {
        return $this->operator('$toString', $expression);
    }

    public function toUpper($expression): static
    {
        return $this->operator('$toUpper', $expression);
    }

    public function trunc($number): static
    {
        return $this->operator('$trunc', $number);
    }

    public function tsIncrement($expression): static
    {
        return $this->operator('$tsIncrement', $expression);
    }

    public function tsSecond($expression): static
    {
        return $this->operator('$tsSecond', $expression);
    }

    public function type($expression): static
    {
        return $this->operator('$type', $expression);
    }

    public function week($expression): static
    {
        return $this->operator('$week', $expression);
    }

    public function year($expression): static
    {
        return $this->operator('$year', $expression);
    }

    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): static
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
    private function accumulatorOperator(string $operator, ...$expressions): static
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
     */
    private function operator(string $operator, $expression): static
    {
        if ($this->currentField) {
            $this->expr[$this->currentField][$operator] = $this->prepareArgument($expression);
        } else {
            $this->expr[$operator] = $this->prepareArgument($expression);
        }

        return $this;
    }

    public function or($expression, ...$expressions): static
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

    public function switch(): static
    {
        $this->operator('$switch', []);

        return $this;
    }

    public function then($expression): static
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

    public function arrayToObject($array): static
    {
        return $this->operator('$arrayToObject', $array);
    }

    public function objectToArray($object): static
    {
        return $this->operator('$objectToArray', $object);
    }

    public function regexFind($input, $regex, $options = null): static
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

    public function regexFindAll($input, $regex, $options = null): static
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

    public function regexMatch($input, $regex, $options = null): static
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

    public function replaceAll($input, $find, $replacement): static
    {
        return $this->operator('$replaceAll', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    public function replaceOne($input, $find, $replacement): static
    {
        return $this->operator('$replaceOne', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    public function round($number, $place = null): static
    {
        return $this->operator('$round', func_get_args());
    }

    public function trim($input, $chars = null): static
    {
        return $this->operator('$trim', func_get_args());
    }

    public function ltrim($input, $chars = null): static
    {
        return $this->operator('$ltrim', func_get_args());
    }

    public function rtrim($input, $chars = null): static
    {
        return $this->operator('$rtrim', func_get_args());
    }

    public function sin($expression): static
    {
        return $this->operator('$sin', $expression);
    }

    public function cos($expression): static
    {
        return $this->operator('$cos', $expression);
    }

    public function tan($expression): static
    {
        return $this->operator('$tan', $expression);
    }

    public function asin($expression): static
    {
        return $this->operator('$asin', $expression);
    }

    public function acos($expression): static
    {
        return $this->operator('$acos', $expression);
    }

    public function atan($expression): static
    {
        return $this->operator('$atan', $expression);
    }

    public function atan2($expression1, $expression2): static
    {
        return $this->operator('$atan2', func_get_args());
    }

    public function asinh($expression): static
    {
        return $this->operator('$asinh', $expression);
    }

    public function acosh($expression): static
    {
        return $this->operator('$acosh', $expression);
    }

    public function atanh($expression): static
    {
        return $this->operator('$atanh', $expression);
    }

    public function sinh($expression): static
    {
        return $this->operator('$sinh', $expression);
    }

    public function cosh($expression): static
    {
        return $this->operator('$cosh', $expression);
    }

    public function tanh($expression): static
    {
        return $this->operator('$tanh', $expression);
    }

    public function degreesToRadians($expression): static
    {
        return $this->operator('$degreesToRadians', $expression);
    }

    public function radiansToDegrees($expression): static
    {
        return $this->operator('$radiansToDegrees', $expression);
    }

    public function convert($input, $to, $onError = null, $onNull = null): static
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

    public function isNumber($expression): static
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
