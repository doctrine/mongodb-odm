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

    /** This constructor is used in {@see self::expr()} */
    final public function __construct(private DocumentManager $dm, private ClassMetadata $class)
    {
    }

    public function abs(mixed $number): static
    {
        return $this->operator('$abs', $number);
    }

    public function accumulator(string|Javascript $init, string|Javascript $accumulate, mixed $accumulateArgs, string|Javascript $merge, mixed $initArgs = null, string|Javascript|null $finalize = null, string $lang = 'js'): static
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

    public function add(mixed $expression1, mixed $expression2, mixed ...$expressions): static
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
    public function addAnd(mixed $expression, mixed ...$expressions): static
    {
        $this->expr['$and'] = array_merge(
            $this->expr['$and'] ?? [],
            array_map($this->prepareArgument(...), func_get_args()),
        );

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
    public function addOr(mixed $expression, mixed ...$expressions): static
    {
        $this->expr['$or'] = array_merge(
            $this->expr['$or'] ?? [],
            array_map($this->prepareArgument(...), func_get_args()),
        );

        return $this;
    }

    public function addToSet(mixed $expression): static
    {
        return $this->operator('$addToSet', $expression);
    }

    public function allElementsTrue(mixed $expression): static
    {
        return $this->operator('$allElementsTrue', $expression);
    }

    public function and(mixed $expression, mixed ...$expressions): static
    {
        return $this->operator('$and', func_get_args());
    }

    public function anyElementTrue(mixed $expression): static
    {
        return $this->operator('$anyElementTrue', $expression);
    }

    public function arrayElemAt(mixed $array, mixed $index): static
    {
        return $this->operator('$arrayElemAt', func_get_args());
    }

    public function avg(mixed $expression, mixed ...$expressions): static
    {
        return $this->accumulatorOperator('$avg', ...func_get_args());
    }

    public function binarySize(mixed $expression): static
    {
        return $this->operator('$binarySize', $expression);
    }

    public function bottom(mixed $output, array $sortBy): static
    {
        return $this->operator('$bottom', ['output' => $output, 'sortBy' => $sortBy]);
    }

    public function bottomN(mixed $output, array $sortBy, mixed $n): static
    {
        return $this->operator('$bottomN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    public function bsonSize(mixed $expression): static
    {
        return $this->operator('$bsonSize', $expression);
    }

    public function case(mixed $expression): static
    {
        $this->requiresSwitchStatement(static::class . '::case');

        $this->switchBranch = ['case' => $expression];

        return $this;
    }

    public function ceil(mixed $number): static
    {
        return $this->operator('$ceil', $number);
    }

    public function cmp(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$cmp', func_get_args());
    }

    public function concat(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        return $this->operator('$concat', func_get_args());
    }

    public function concatArrays(mixed $array1, mixed $array2, mixed ...$arrays): static
    {
        return $this->operator('$concatArrays', func_get_args());
    }

    public function cond(mixed $if, mixed $then, mixed $else): static
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
     */
    public static function convertExpression(mixed $expression): mixed
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

    public function covariancePop(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$covariancePop', func_get_args());
    }

    public function covarianceSamp(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$covarianceSamp', func_get_args());
    }

    public function dateAdd(mixed $startDate, mixed $unit, mixed $amount, mixed $timezone = null): static
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

    public function dateDiff(mixed $startDate, mixed $endDate, mixed $unit, mixed $timezone = null, mixed $startOfWeek = null): static
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

    public function dateFromParts(mixed $year = null, mixed $isoWeekYear = null, mixed $month = null, mixed $isoWeek = null, mixed $day = null, mixed $isoDayOfWeek = null, mixed $hour = null, mixed $minute = null, mixed $second = null, mixed $millisecond = null, mixed $timezone = null): static
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

    public function dateFromString(mixed $dateString, mixed $format = null, mixed $timezone = null, mixed $onError = null, mixed $onNull = null): static
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

    public function dateSubtract(mixed $startDate, mixed $unit, mixed $amount, mixed $timezone = null): static
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

    public function dateToParts(mixed $date, mixed $timezone = null, mixed $iso8601 = null): static
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

    public function dateToString(string $format, mixed $expression, mixed $timezone = null, mixed $onNull = null): static
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

    public function dateTrunc(mixed $date, mixed $unit, mixed $binSize = null, mixed $timezone = null, mixed $startOfWeek = null): static
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

    public function dayOfMonth(mixed $expression): static
    {
        return $this->operator('$dayOfMonth', $expression);
    }

    public function dayOfWeek(mixed $expression): static
    {
        return $this->operator('$dayOfWeek', $expression);
    }

    public function dayOfYear(mixed $expression): static
    {
        return $this->operator('$dayOfYear', $expression);
    }

    public function default(mixed $expression): static
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

    public function derivative(mixed $input, string $unit): static
    {
        return $this->operator('$derivative', ['input' => $input, 'unit' => $unit]);
    }

    public function divide(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$divide', func_get_args());
    }

    public function documentNumber(): static
    {
        return $this->operator('$documentNumber', []);
    }

    public function eq(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$eq', func_get_args());
    }

    public function exp(mixed $exponent): static
    {
        return $this->operator('$exp', $exponent);
    }

    public function expMovingAvg(mixed $input, ?int $n = null, ?float $alpha = null): static
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

    public function expression(mixed $value): static
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

    public function filter(mixed $input, mixed $as, mixed $cond): static
    {
        return $this->operator('$filter', ['input' => $input, 'as' => $as, 'cond' => $cond]);
    }

    public function first(mixed $expression): static
    {
        return $this->operator('$first', $expression);
    }

    public function firstN(mixed $expression, mixed $n): static
    {
        return $this->operator('$firstN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function function(string|Javascript $body, mixed $args, string $lang = 'js'): static
    {
        return $this->operator('$function', ['body' => $body, 'args' => $args, 'lang' => $lang]);
    }

    public function floor(mixed $number): static
    {
        return $this->operator('$floor', $number);
    }

    /** @return array<string, mixed> */
    public function getExpression(): array
    {
        return $this->expr;
    }

    public function getField(mixed $field, mixed $input = null): static
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

    public function gt(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$gt', func_get_args());
    }

    public function gte(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$gte', func_get_args());
    }

    public function hour(mixed $expression): static
    {
        return $this->operator('$hour', $expression);
    }

    public function ifNull(mixed $expression, mixed $replacementExpression): static
    {
        return $this->operator('$ifNull', func_get_args());
    }

    public function in(mixed $expression, mixed $arrayExpression): static
    {
        return $this->operator('$in', func_get_args());
    }

    public function indexOfArray(mixed $arrayExpression, mixed $searchExpression, mixed $start = null, mixed $end = null): static
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

    public function indexOfBytes(mixed $stringExpression, mixed $substringExpression, string|int|null $start = null, string|int|null $end = null): static
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

    public function indexOfCP(mixed $stringExpression, mixed $substringExpression, string|int|null $start = null, string|int|null $end = null): static
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

    public function integral(mixed $input, string $unit): static
    {
        return $this->operator('$integral', ['input' => $input, 'unit' => $unit]);
    }

    public function isArray(mixed $expression): static
    {
        return $this->operator('$isArray', $expression);
    }

    public function isoDayOfWeek(mixed $expression): static
    {
        return $this->operator('$isoDayOfWeek', $expression);
    }

    public function isoWeek(mixed $expression): static
    {
        return $this->operator('$isoWeek', $expression);
    }

    public function isoWeekYear(mixed $expression): static
    {
        return $this->operator('$isoWeekYear', $expression);
    }

    public function last(mixed $expression): static
    {
        return $this->operator('$last', $expression);
    }

    public function lastN(mixed $expression, mixed $n): static
    {
        return $this->operator('$lastN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function let(mixed $vars, mixed $in): static
    {
        return $this->operator('$let', ['vars' => $vars, 'in' => $in]);
    }

    public function linearFill(mixed $expression): static
    {
        return $this->operator('$linearFill', $expression);
    }

    public function literal(mixed $value): static
    {
        return $this->operator('$literal', $value);
    }

    public function ln(mixed $number): static
    {
        return $this->operator('$ln', $number);
    }

    public function locf(mixed $expression): static
    {
        return $this->operator('$locf', $expression);
    }

    public function log(mixed $number, mixed $base): static
    {
        return $this->operator('$log', func_get_args());
    }

    public function log10(mixed $number): static
    {
        return $this->operator('$log10', $number);
    }

    public function lt(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$lt', func_get_args());
    }

    public function lte(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$lte', func_get_args());
    }

    public function map(mixed $input, string $as, mixed $in): static
    {
        return $this->operator('$map', ['input' => $input, 'as' => $as, 'in' => $in]);
    }

    public function max(mixed $expression, mixed ...$expressions): static
    {
        return $this->accumulatorOperator('$max', ...func_get_args());
    }

    public function maxN(mixed $expression, mixed $n): static
    {
        return $this->operator('$maxN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function mergeObjects(mixed $expression, mixed ...$expressions): static
    {
        return $this->accumulatorOperator('$mergeObjects', ...func_get_args());
    }

    public function meta(mixed $metaDataKeyword): static
    {
        return $this->operator('$meta', $metaDataKeyword);
    }

    public function millisecond(mixed $expression): static
    {
        return $this->operator('$millisecond', $expression);
    }

    public function min(mixed $expression, mixed ...$expressions): static
    {
        return $this->accumulatorOperator('$min', ...func_get_args());
    }

    public function minN(mixed $expression, mixed $n): static
    {
        return $this->operator('$minN', [
            'input' => $expression,
            'n' => $n,
        ]);
    }

    public function minute(mixed $expression): static
    {
        return $this->operator('$minute', $expression);
    }

    public function mod(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$mod', func_get_args());
    }

    public function month(mixed $expression): static
    {
        return $this->operator('$month', $expression);
    }

    public function multiply(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        return $this->operator('$multiply', func_get_args());
    }

    public function ne(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$ne', func_get_args());
    }

    public function not(mixed $expression): static
    {
        return $this->operator('$not', $expression);
    }

    public function pow(mixed $number, mixed $exponent): static
    {
        return $this->operator('$pow', func_get_args());
    }

    public function push(mixed $expression): static
    {
        return $this->operator('$push', $expression);
    }

    public function rand(): static
    {
        return $this->operator('$rand', []);
    }

    public function range(mixed $start, mixed $end, mixed $step = null): static
    {
        return $this->operator('$range', func_get_args());
    }

    public function rank(): static
    {
        return $this->operator('$rank', []);
    }

    public function reduce(mixed $input, mixed $initialValue, mixed $in): static
    {
        return $this->operator('$reduce', ['input' => $input, 'initialValue' => $initialValue, 'in' => $in]);
    }

    public function reverseArray(mixed $expression): static
    {
        return $this->operator('$reverseArray', $expression);
    }

    public function sampleRate(float $rate): static
    {
        return $this->operator('$sampleRate', $rate);
    }

    public function second(mixed $expression): static
    {
        return $this->operator('$second', $expression);
    }

    public function setDifference(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$setDifference', func_get_args());
    }

    public function setEquals(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        return $this->operator('$setEquals', func_get_args());
    }

    public function setField(mixed $field, mixed $input, mixed $value): static
    {
        return $this->operator('$setField', ['field' => $field, 'input' => $input, 'value' => $value]);
    }

    public function setIntersection(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        return $this->operator('$setIntersection', func_get_args());
    }

    public function setIsSubset(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$setIsSubset', func_get_args());
    }

    public function setUnion(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        return $this->operator('$setUnion', func_get_args());
    }

    public function shift(mixed $output, int $by, mixed $default = null): static
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

    public function size(mixed $expression): static
    {
        return $this->operator('$size', $expression);
    }

    public function slice(mixed $array, mixed $n, mixed $position = null): static
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

    public function sortArray(mixed $input, array $sortBy): static
    {
        return $this->operator('$sortArray', [
            'input' => $input,
            'sortBy' => $sortBy,
        ]);
    }

    public function split(mixed $string, mixed $delimiter): static
    {
        return $this->operator('$split', func_get_args());
    }

    public function sqrt(mixed $expression): static
    {
        return $this->operator('$sqrt', $expression);
    }

    public function stdDevPop(mixed $expression, mixed ...$expressions): static
    {
        return $this->accumulatorOperator('$stdDevPop', ...func_get_args());
    }

    public function stdDevSamp(mixed $expression, mixed ...$expressions): static
    {
        return $this->accumulatorOperator('$stdDevSamp', ...func_get_args());
    }

    public function strcasecmp(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$strcasecmp', func_get_args());
    }

    public function strLenBytes(mixed $string): static
    {
        return $this->operator('$strLenBytes', $string);
    }

    public function strLenCP(mixed $string): static
    {
        return $this->operator('$strLenCP', $string);
    }

    public function substr(mixed $string, mixed $start, mixed $length): static
    {
        return $this->operator('$substr', func_get_args());
    }

    public function substrBytes(mixed $string, mixed $start, mixed $count): static
    {
        return $this->operator('$substrBytes', func_get_args());
    }

    public function substrCP(mixed $string, mixed $start, mixed $count): static
    {
        return $this->operator('$substrCP', func_get_args());
    }

    public function subtract(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$subtract', func_get_args());
    }

    public function sum(mixed $expression, mixed ...$expressions): static
    {
        return $this->accumulatorOperator('$sum', ...func_get_args());
    }

    public function toBool(mixed $expression): static
    {
        return $this->operator('$toBool', $expression);
    }

    public function toDate(mixed $expression): static
    {
        return $this->operator('$toDate', $expression);
    }

    public function toDecimal(mixed $expression): static
    {
        return $this->operator('$toDecimal', $expression);
    }

    public function toDouble(mixed $expression): static
    {
        return $this->operator('$toDouble', $expression);
    }

    public function toInt(mixed $expression): static
    {
        return $this->operator('$toInt', $expression);
    }

    public function toLong(mixed $expression): static
    {
        return $this->operator('$toLong', $expression);
    }

    public function toLower(mixed $expression): static
    {
        return $this->operator('$toLower', $expression);
    }

    public function toObjectId(mixed $expression): static
    {
        return $this->operator('$toObjectId', $expression);
    }

    public function top(mixed $output, array $sortBy): static
    {
        return $this->operator('$top', ['output' => $output, 'sortBy' => $sortBy]);
    }

    public function topN(mixed $output, array $sortBy, mixed $n): static
    {
        return $this->operator('$topN', ['output' => $output, 'sortBy' => $sortBy, 'n' => $n]);
    }

    public function toString(mixed $expression): static
    {
        return $this->operator('$toString', $expression);
    }

    public function toUpper(mixed $expression): static
    {
        return $this->operator('$toUpper', $expression);
    }

    public function trunc(mixed $number): static
    {
        return $this->operator('$trunc', $number);
    }

    public function tsIncrement(mixed $expression): static
    {
        return $this->operator('$tsIncrement', $expression);
    }

    public function tsSecond(mixed $expression): static
    {
        return $this->operator('$tsSecond', $expression);
    }

    public function type(mixed $expression): static
    {
        return $this->operator('$type', $expression);
    }

    public function week(mixed $expression): static
    {
        return $this->operator('$week', $expression);
    }

    public function year(mixed $expression): static
    {
        return $this->operator('$year', $expression);
    }

    public function zip(mixed $inputs, ?bool $useLongestLength = null, mixed $defaults = null): static
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
     */
    private function accumulatorOperator(string $operator, mixed ...$expressions): static
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
     */
    private function prepareArgument(mixed $expression): mixed
    {
        if (is_string($expression) && substr($expression, 0, 1) === '$') {
            return '$' . $this->getDocumentPersister()->prepareFieldName(substr($expression, 1));
        }

        if (is_array($expression)) {
            return array_map($this->prepareArgument(...), $expression);
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
     */
    private function operator(string $operator, mixed $expression): static
    {
        if ($this->currentField) {
            $this->expr[$this->currentField][$operator] = $this->prepareArgument($expression);
        } else {
            $this->expr[$operator] = $this->prepareArgument($expression);
        }

        return $this;
    }

    public function or(mixed $expression, mixed ...$expressions): static
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

    public function then(mixed $expression): static
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

    public function arrayToObject(mixed $array): static
    {
        return $this->operator('$arrayToObject', $array);
    }

    public function objectToArray(mixed $object): static
    {
        return $this->operator('$objectToArray', $object);
    }

    public function regexFind(mixed $input, mixed $regex, ?string $options = null): static
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

    public function regexFindAll(mixed $input, mixed $regex, ?string $options = null): static
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

    public function regexMatch(mixed $input, mixed $regex, ?string $options = null): static
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

    public function replaceAll(mixed $input, mixed $find, mixed $replacement): static
    {
        return $this->operator('$replaceAll', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    public function replaceOne(mixed $input, mixed $find, mixed $replacement): static
    {
        return $this->operator('$replaceOne', [
            'input' => $input,
            'find' => $find,
            'replacement' => $replacement,
        ]);
    }

    public function round(mixed $number, mixed $place = null): static
    {
        return $this->operator('$round', func_get_args());
    }

    public function trim(mixed $input, mixed $chars = null): static
    {
        return $this->operator('$trim', func_get_args());
    }

    public function ltrim(mixed $input, mixed $chars = null): static
    {
        return $this->operator('$ltrim', func_get_args());
    }

    public function rtrim(mixed $input, mixed $chars = null): static
    {
        return $this->operator('$rtrim', func_get_args());
    }

    public function sin(mixed $expression): static
    {
        return $this->operator('$sin', $expression);
    }

    public function cos(mixed $expression): static
    {
        return $this->operator('$cos', $expression);
    }

    public function tan(mixed $expression): static
    {
        return $this->operator('$tan', $expression);
    }

    public function asin(mixed $expression): static
    {
        return $this->operator('$asin', $expression);
    }

    public function acos(mixed $expression): static
    {
        return $this->operator('$acos', $expression);
    }

    public function atan(mixed $expression): static
    {
        return $this->operator('$atan', $expression);
    }

    public function atan2(mixed $expression1, mixed $expression2): static
    {
        return $this->operator('$atan2', func_get_args());
    }

    public function asinh(mixed $expression): static
    {
        return $this->operator('$asinh', $expression);
    }

    public function acosh(mixed $expression): static
    {
        return $this->operator('$acosh', $expression);
    }

    public function atanh(mixed $expression): static
    {
        return $this->operator('$atanh', $expression);
    }

    public function sinh(mixed $expression): static
    {
        return $this->operator('$sinh', $expression);
    }

    public function cosh(mixed $expression): static
    {
        return $this->operator('$cosh', $expression);
    }

    public function tanh(mixed $expression): static
    {
        return $this->operator('$tanh', $expression);
    }

    public function degreesToRadians(mixed $expression): static
    {
        return $this->operator('$degreesToRadians', $expression);
    }

    public function radiansToDegrees(mixed $expression): static
    {
        return $this->operator('$radiansToDegrees', $expression);
    }

    public function convert(mixed $input, mixed $to, mixed $onError = null, mixed $onNull = null): static
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

    public function isNumber(mixed $expression): static
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
