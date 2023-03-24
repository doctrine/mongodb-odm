<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Operator\AccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArithmeticOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ArrayOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\BooleanOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ComparisonOperators;
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
    ComparisonOperators,
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

    /** @return static */
    public function abs($number): self
    {
        $this->expr->abs($number);

        return $this;
    }

    /** @return static */
    public function acos($expression): self
    {
        $this->expr->acos($expression);

        return $this;
    }

    /** @return static */
    public function acosh($expression): self
    {
        $this->expr->acosh($expression);

        return $this;
    }

    /** @return static */
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

    /** @return static */
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

    /** @return static */
    public function addOr($expression, ...$expressions): self
    {
        $this->expr->addOr(...func_get_args());

        return $this;
    }

    /** @return static */
    public function allElementsTrue($expression): self
    {
        $this->expr->allElementsTrue($expression);

        return $this;
    }

    /** @return static */
    public function and($expression, ...$expressions): self
    {
        $this->expr->and($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function anyElementTrue($expression): self
    {
        $this->expr->anyElementTrue($expression);

        return $this;
    }

    /** @return static */
    public function arrayElemAt($array, $index): self
    {
        $this->expr->arrayElemAt($array, $index);

        return $this;
    }

    /** @return static */
    public function arrayToObject($array): self
    {
        $this->expr->arrayToObject($array);

        return $this;
    }

    /** @return static */
    public function atan($expression): self
    {
        $this->expr->atan($expression);

        return $this;
    }

    /** @return static */
    public function asin($expression): self
    {
        $this->expr->asin($expression);

        return $this;
    }

    /** @return static */
    public function asinh($expression): self
    {
        $this->expr->asinh($expression);

        return $this;
    }

    /** @return static */
    public function atan2($expression1, $expression2): self
    {
        $this->expr->atan2($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function atanh($expression): self
    {
        $this->expr->atanh($expression);

        return $this;
    }

    /** @return static */
    public function avg($expression, ...$expressions): self
    {
        $this->expr->avg(...func_get_args());

        return $this;
    }

    /** @return static */
    public function binarySize($expression): self
    {
        $this->expr->binarySize($expression);

        return $this;
    }

    /** @return static */
    public function bsonSize($expression): self
    {
        $this->expr->bsonSize($expression);

        return $this;
    }

    /** @return static */
    public function case($expression): self
    {
        $this->expr->case($expression);

        return $this;
    }

    /** @return static */
    public function ceil($number): self
    {
        $this->expr->ceil($number);

        return $this;
    }

    /** @return static */
    public function cmp($expression1, $expression2): self
    {
        $this->expr->cmp($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function concat($expression1, $expression2, ...$expressions): self
    {
        $this->expr->concat(...func_get_args());

        return $this;
    }

    /** @return static */
    public function concatArrays($array1, $array2, ...$arrays): self
    {
        $this->expr->concatArrays(...func_get_args());

        return $this;
    }

    /** @return static */
    public function cond($if, $then, $else): self
    {
        $this->expr->cond($if, $then, $else);

        return $this;
    }

    /** @return static */
    public function convert($input, $to, $onError = null, $onNull = null): self
    {
        $this->expr->convert($input, $to, $onError, $onNull);

        return $this;
    }

    /** @return static */
    public function cos($expression): self
    {
        $this->expr->cos($expression);

        return $this;
    }

    /** @return static */
    public function cosh($expression): self
    {
        $this->expr->cosh($expression);

        return $this;
    }

    /** @return static */
    public function dateAdd($startDate, $unit, $amount, $timezone = null): self
    {
        $this->expr->dateAdd($startDate, $unit, $amount, $timezone);

        return $this;
    }

    /** @return static */
    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): self
    {
        $this->expr->dateDiff($startDate, $endDate, $unit, $timezone, $startOfWeek);

        return $this;
    }

    /** @return static */
    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): self
    {
        $this->expr->dateFromParts($year, $isoWeekYear, $month, $isoWeek, $day, $isoDayOfWeek, $hour, $minute, $second, $millisecond, $timezone);

        return $this;
    }

    /** @return static */
    public function dateFromString($dateString, $format = null, $timezone = null, $onError = null, $onNull = null): self
    {
        $this->expr->dateFromString($dateString, $format, $timezone, $onError, $onNull);

        return $this;
    }

    /** @return static */
    public function dateSubtract($startDate, $unit, $amount, $timezone = null): self
    {
        $this->expr->dateSubtract($startDate, $unit, $amount, $timezone);

        return $this;
    }

    /** @return static */
    public function dateToParts($date, $timezone = null, $iso8601 = null): self
    {
        $this->expr->dateToParts($date, $timezone, $iso8601);

        return $this;
    }

    /** @return static */
    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): self
    {
        $this->expr->dateToString($format, $expression, $timezone, $onNull);

        return $this;
    }

    /** @return static */
    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): self
    {
        $this->expr->dateTrunc($date, $unit, $binSize, $timezone, $startOfWeek);

        return $this;
    }

    /** @return static */
    public function dayOfMonth($expression): self
    {
        $this->expr->dayOfMonth($expression);

        return $this;
    }

    /** @return static */
    public function dayOfWeek($expression): self
    {
        $this->expr->dayOfWeek($expression);

        return $this;
    }

    /** @return static */
    public function dayOfYear($expression): self
    {
        $this->expr->dayOfYear($expression);

        return $this;
    }

    /** @return static */
    public function default($expression): self
    {
        $this->expr->default($expression);

        return $this;
    }

    /** @return static */
    public function degreesToRadians($expression): self
    {
        $this->expr->degreesToRadians($expression);

        return $this;
    }

    /** @return static */
    public function divide($expression1, $expression2): self
    {
        $this->expr->divide($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function eq($expression1, $expression2): self
    {
        $this->expr->eq($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function exp($exponent): self
    {
        $this->expr->exp($exponent);

        return $this;
    }

    /** @return static */
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

    /** @return static */
    public function filter($input, $as, $cond): self
    {
        $this->expr->filter($input, $as, $cond);

        return $this;
    }

    /** @return static */
    public function first($expression): self
    {
        $this->expr->first($expression);

        return $this;
    }

    /** @return static */
    public function firstN($expression, $n): self
    {
        $this->expr->firstN($expression, $n);

        return $this;
    }

    /** @return static */
    public function floor($number): self
    {
        $this->expr->floor($number);

        return $this;
    }

    /** @return static */
    public function getField($field, $input = null): self
    {
        $this->expr->getField($field, $input);

        return $this;
    }

    /** @return static */
    public function gt($expression1, $expression2): self
    {
        $this->expr->gt($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function gte($expression1, $expression2): self
    {
        $this->expr->gte($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function hour($expression): self
    {
        $this->expr->hour($expression);

        return $this;
    }

    /** @return static */
    public function in($expression, $arrayExpression): self
    {
        $this->expr->in($expression, $arrayExpression);

        return $this;
    }

    /** @return static */
    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfArray($arrayExpression, $searchExpression, $start, $end);

        return $this;
    }

    /** @return static */
    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfBytes($stringExpression, $substringExpression, $start, $end);

        return $this;
    }

    /** @return static */
    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfCP($stringExpression, $substringExpression, $start, $end);

        return $this;
    }

    /** @return static */
    public function ifNull($expression, $replacementExpression): self
    {
        $this->expr->ifNull($expression, $replacementExpression);

        return $this;
    }

    /** @return static */
    public function isArray($expression): self
    {
        $this->expr->isArray($expression);

        return $this;
    }

    /** @return static */
    public function isNumber($expression): self
    {
        $this->expr->isNumber($expression);

        return $this;
    }

    /** @return static */
    public function isoDayOfWeek($expression): self
    {
        $this->expr->isoDayOfWeek($expression);

        return $this;
    }

    /** @return static */
    public function isoWeek($expression): self
    {
        $this->expr->isoWeek($expression);

        return $this;
    }

    /** @return static */
    public function isoWeekYear($expression): self
    {
        $this->expr->isoWeekYear($expression);

        return $this;
    }

    /** @return static */
    public function last($expression): self
    {
        $this->expr->last($expression);

        return $this;
    }

    /** @return static */
    public function lastN($expression, $n): self
    {
        $this->expr->lastN($expression, $n);

        return $this;
    }

    /** @return static */
    public function let($vars, $in): self
    {
        $this->expr->let($vars, $in);

        return $this;
    }

    /** @return static */
    public function literal($value): self
    {
        $this->expr->literal($value);

        return $this;
    }

    /** @return static */
    public function ln($number): self
    {
        $this->expr->ln($number);

        return $this;
    }

    /** @return static */
    public function log($number, $base): self
    {
        $this->expr->log($number, $base);

        return $this;
    }

    /** @return static */
    public function log10($number): self
    {
        $this->expr->log10($number);

        return $this;
    }

    /** @return static */
    public function lt($expression1, $expression2): self
    {
        $this->expr->lt($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function lte($expression1, $expression2): self
    {
        $this->expr->lte($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function ltrim($input, $chars = null): self
    {
        $this->expr->ltrim($input, $chars);

        return $this;
    }

    /** @return static */
    public function map($input, $as, $in): self
    {
        $this->expr->map($input, $as, $in);

        return $this;
    }

    /** @return static */
    public function max($expression, ...$expressions): self
    {
        $this->expr->max($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function maxN($expression, $n): self
    {
        $this->expr->maxN($expression, $n);

        return $this;
    }

    /** @return static */
    public function mergeObjects($expression, ...$expressions): self
    {
        $this->expr->mergeObjects($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function meta($metaDataKeyword): self
    {
        $this->expr->meta($metaDataKeyword);

        return $this;
    }

    /** @return static */
    public function millisecond($expression): self
    {
        $this->expr->millisecond($expression);

        return $this;
    }

    /** @return static */
    public function min($expression, ...$expressions): self
    {
        $this->expr->min($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function minN($expression, $n): self
    {
        $this->expr->minN($expression, $n);

        return $this;
    }

    /** @return static */
    public function minute($expression): self
    {
        $this->expr->minute($expression);

        return $this;
    }

    /** @return static */
    public function mod($expression1, $expression2): self
    {
        $this->expr->mod($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function month($expression): self
    {
        $this->expr->month($expression);

        return $this;
    }

    /** @return static */
    public function multiply($expression1, $expression2, ...$expressions): self
    {
        $this->expr->multiply(...func_get_args());

        return $this;
    }

    /** @return static */
    public function ne($expression1, $expression2): self
    {
        $this->expr->ne($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function not($expression): self
    {
        $this->expr->not($expression);

        return $this;
    }

    /** @return static */
    public function objectToArray($object): self
    {
        $this->expr->objectToArray($object);

        return $this;
    }

    /** @return static */
    public function or($expression, ...$expressions): self
    {
        $this->expr->or($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function pow($number, $exponent): self
    {
        $this->expr->pow($number, $exponent);

        return $this;
    }

    /** @return static */
    public function range($start, $end, $step = 1): self
    {
        $this->expr->range($start, $end, $step);

        return $this;
    }

    /** @return static */
    public function reduce($input, $initialValue, $in): self
    {
        $this->expr->reduce($input, $initialValue, $in);

        return $this;
    }

    /** @return static */
    public function regexFind($input, $regex, $options = null): self
    {
        $this->expr->regexFind($input, $regex, $options);

        return $this;
    }

    /** @return static */
    public function regexFindAll($input, $regex, $options = null): self
    {
        $this->expr->regexFindAll($input, $regex, $options);

        return $this;
    }

    /** @return static */
    public function regexMatch($input, $regex, $options = null): self
    {
        $this->expr->regexMatch($input, $regex, $options);

        return $this;
    }

    /** @return static */
    public function replaceAll($input, $find, $replacement): self
    {
        $this->expr->replaceAll($input, $find, $replacement);

        return $this;
    }

    /** @return static */
    public function replaceOne($input, $find, $replacement): self
    {
        $this->expr->replaceOne($input, $find, $replacement);

        return $this;
    }

    /** @return static */
    public function reverseArray($expression): self
    {
        $this->expr->reverseArray($expression);

        return $this;
    }

    /** @return static */
    public function rtrim($input, $chars = null): self
    {
        $this->expr->rtrim($input, $chars);

        return $this;
    }

    /** @return static */
    public function round($number, $place = null): self
    {
        $this->expr->round($number, $place);

        return $this;
    }

    /** @return static */
    public function radiansToDegrees($expression): self
    {
        $this->expr->radiansToDegrees($expression);

        return $this;
    }

    /** @return static */
    public function rand(): self
    {
        $this->expr->rand();

        return $this;
    }

    /** @return static */
    public function sampleRate(float $rate): self
    {
        $this->expr->sampleRate($rate);

        return $this;
    }

    /** @return static */
    public function second($expression): self
    {
        $this->expr->second($expression);

        return $this;
    }

    /** @return static */
    public function setDifference($expression1, $expression2): self
    {
        $this->expr->setDifference($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function setEquals($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setEquals(...func_get_args());

        return $this;
    }

    /** @return static */
    public function setField($field, $input, $value): self
    {
        $this->expr->setField($field, $input, $value);

        return $this;
    }

    /** @return static */
    public function setIntersection($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setIntersection(...func_get_args());

        return $this;
    }

    /** @return static */
    public function setIsSubset($expression1, $expression2): self
    {
        $this->expr->setIsSubset($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function setUnion($expression1, $expression2, ...$expressions): self
    {
        $this->expr->setUnion(...func_get_args());

        return $this;
    }

    /** @return static */
    public function sin($expression): self
    {
        $this->expr->sin($expression);

        return $this;
    }

    /** @return static */
    public function sinh($expression): self
    {
        $this->expr->sinh($expression);

        return $this;
    }

    /** @return static */
    public function size($expression): self
    {
        $this->expr->size($expression);

        return $this;
    }

    /** @return static */
    public function slice($array, $n, $position = null): self
    {
        $this->expr->slice($array, $n, $position);

        return $this;
    }

    /** @return static */
    public function sortArray($input, $sortBy): self
    {
        $this->expr->sortArray($input, $sortBy);

        return $this;
    }

    /** @return static */
    public function split($string, $delimiter): self
    {
        $this->expr->split($string, $delimiter);

        return $this;
    }

    /** @return static */
    public function sqrt($expression): self
    {
        $this->expr->sqrt($expression);

        return $this;
    }

    /** @return static */
    public function stdDevPop($expression, ...$expressions): self
    {
        $this->expr->stdDevPop($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function stdDevSamp($expression, ...$expressions): self
    {
        $this->expr->stdDevSamp($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function strcasecmp($expression1, $expression2): self
    {
        $this->expr->strcasecmp($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function strLenBytes($string): self
    {
        $this->expr->strLenBytes($string);

        return $this;
    }

    /** @return static */
    public function strLenCP($string): self
    {
        $this->expr->strLenCP($string);

        return $this;
    }

    /** @return static */
    public function substr($string, $start, $length): self
    {
        $this->expr->substr($string, $start, $length);

        return $this;
    }

    /** @return static */
    public function substrBytes($string, $start, $count): self
    {
        $this->expr->substrBytes($string, $start, $count);

        return $this;
    }

    /** @return static */
    public function substrCP($string, $start, $count): self
    {
        $this->expr->substrCP($string, $start, $count);

        return $this;
    }

    /** @return static */
    public function subtract($expression1, $expression2): self
    {
        $this->expr->subtract($expression1, $expression2);

        return $this;
    }

    /** @return static */
    public function sum($expression, ...$expressions): self
    {
        $this->expr->sum($expression, ...$expressions);

        return $this;
    }

    /** @return static */
    public function switch(): self
    {
        $this->expr->switch();

        return $this;
    }

    /** @return static */
    public function tan($expression): self
    {
        $this->expr->tan($expression);

        return $this;
    }

    /** @return static */
    public function tanh($expression): self
    {
        $this->expr->tanh($expression);

        return $this;
    }

    /** @return static */
    public function then($expression): self
    {
        $this->expr->then($expression);

        return $this;
    }

    /** @return static */
    public function toBool($expression): self
    {
        $this->expr->toBool($expression);

        return $this;
    }

    /** @return static */
    public function toDate($expression): self
    {
        $this->expr->toDate($expression);

        return $this;
    }

    /** @return static */
    public function toDecimal($expression): self
    {
        $this->expr->toDecimal($expression);

        return $this;
    }

    /** @return static */
    public function toDouble($expression): self
    {
        $this->expr->toDouble($expression);

        return $this;
    }

    /** @return static */
    public function toInt($expression): self
    {
        $this->expr->toInt($expression);

        return $this;
    }

    /** @return static */
    public function toLong($expression): self
    {
        $this->expr->toLong($expression);

        return $this;
    }

    /** @return static */
    public function toObjectId($expression): self
    {
        $this->expr->toObjectId($expression);

        return $this;
    }

    /** @return static */
    public function toString($expression): self
    {
        $this->expr->toString($expression);

        return $this;
    }

    /** @return static */
    public function toLower($expression): self
    {
        $this->expr->toLower($expression);

        return $this;
    }

    /** @return static */
    public function toUpper($expression): self
    {
        $this->expr->toUpper($expression);

        return $this;
    }

    /** @return static */
    public function trim($input, $chars = null): self
    {
        $this->expr->trim($input, $chars);

        return $this;
    }

    /** @return static */
    public function trunc($number): self
    {
        $this->expr->trunc($number);

        return $this;
    }

    /** @return static */
    public function tsIncrement($expression): self
    {
        $this->expr->tsIncrement($expression);

        return $this;
    }

    /** @return static */
    public function tsSecond($expression): self
    {
        $this->expr->tsSecond($expression);

        return $this;
    }

    /** @return static */
    public function type($expression): self
    {
        $this->expr->type($expression);

        return $this;
    }

    /** @return static */
    public function week($expression): self
    {
        $this->expr->week($expression);

        return $this;
    }

    /** @return static */
    public function year($expression): self
    {
        $this->expr->year($expression);

        return $this;
    }

    /** @return static */
    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): self
    {
        $this->expr->zip($inputs, $useLongestLength, $defaults);

        return $this;
    }
}
