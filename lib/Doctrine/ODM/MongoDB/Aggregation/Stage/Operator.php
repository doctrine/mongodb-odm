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
        $this->expr->abs(...func_get_args());

        return $this;
    }

    /** @return static */
    public function acos($expression): self
    {
        $this->expr->acos(...func_get_args());

        return $this;
    }

    /** @return static */
    public function acosh($expression): self
    {
        $this->expr->acosh(...func_get_args());

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
        $this->expr->allElementsTrue(...func_get_args());

        return $this;
    }

    /** @return static */
    public function and($expression, ...$expressions): self
    {
        $this->expr->and(...func_get_args());

        return $this;
    }

    /** @return static */
    public function anyElementTrue($expression): self
    {
        $this->expr->anyElementTrue(...func_get_args());

        return $this;
    }

    /** @return static */
    public function arrayElemAt($array, $index): self
    {
        $this->expr->arrayElemAt(...func_get_args());

        return $this;
    }

    /** @return static */
    public function arrayToObject($array): self
    {
        $this->expr->arrayToObject(...func_get_args());

        return $this;
    }

    /** @return static */
    public function atan($expression): self
    {
        $this->expr->atan(...func_get_args());

        return $this;
    }

    /** @return static */
    public function asin($expression): self
    {
        $this->expr->asin(...func_get_args());

        return $this;
    }

    /** @return static */
    public function asinh($expression): self
    {
        $this->expr->asinh(...func_get_args());

        return $this;
    }

    /** @return static */
    public function atan2($expression1, $expression2): self
    {
        $this->expr->atan2(...func_get_args());

        return $this;
    }

    /** @return static */
    public function atanh($expression): self
    {
        $this->expr->atanh(...func_get_args());

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
        $this->expr->binarySize(...func_get_args());

        return $this;
    }

    /** @return static */
    public function bsonSize($expression): self
    {
        $this->expr->bsonSize(...func_get_args());

        return $this;
    }

    /** @return static */
    public function case($expression): self
    {
        $this->expr->case(...func_get_args());

        return $this;
    }

    /** @return static */
    public function ceil($number): self
    {
        $this->expr->ceil(...func_get_args());

        return $this;
    }

    /** @return static */
    public function cmp($expression1, $expression2): self
    {
        $this->expr->cmp(...func_get_args());

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
        $this->expr->cond(...func_get_args());

        return $this;
    }

    /** @return static */
    public function convert($input, $to, $onError = null, $onNull = null): self
    {
        $this->expr->convert(...func_get_args());

        return $this;
    }

    /** @return static */
    public function cos($expression): self
    {
        $this->expr->cos(...func_get_args());

        return $this;
    }

    /** @return static */
    public function cosh($expression): self
    {
        $this->expr->cosh(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateAdd($startDate, $unit, $amount, $timezone = null): self
    {
        $this->expr->dateAdd(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): self
    {
        $this->expr->dateDiff(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): self
    {
        $this->expr->dateFromParts(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateFromString($dateString, $format = null, $timezone = null, $onError = null, $onNull = null): self
    {
        $this->expr->dateFromString(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateSubtract($startDate, $unit, $amount, $timezone = null): self
    {
        $this->expr->dateSubtract(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateToParts($date, $timezone = null, $iso8601 = null): self
    {
        $this->expr->dateToParts(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): self
    {
        $this->expr->dateToString(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): self
    {
        $this->expr->dateTrunc(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dayOfMonth($expression): self
    {
        $this->expr->dayOfMonth(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dayOfWeek($expression): self
    {
        $this->expr->dayOfWeek(...func_get_args());

        return $this;
    }

    /** @return static */
    public function dayOfYear($expression): self
    {
        $this->expr->dayOfYear(...func_get_args());

        return $this;
    }

    /** @return static */
    public function default($expression): self
    {
        $this->expr->default(...func_get_args());

        return $this;
    }

    /** @return static */
    public function degreesToRadians($expression): self
    {
        $this->expr->degreesToRadians(...func_get_args());

        return $this;
    }

    /** @return static */
    public function divide($expression1, $expression2): self
    {
        $this->expr->divide(...func_get_args());

        return $this;
    }

    /** @return static */
    public function eq($expression1, $expression2): self
    {
        $this->expr->eq(...func_get_args());

        return $this;
    }

    /** @return static */
    public function exp($exponent): self
    {
        $this->expr->exp(...func_get_args());

        return $this;
    }

    /** @return static */
    public function expression($value)
    {
        $this->expr->expression(...func_get_args());

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
        $this->expr->field(...func_get_args());

        return $this;
    }

    /** @return static */
    public function filter($input, $as, $cond): self
    {
        $this->expr->filter(...func_get_args());

        return $this;
    }

    /** @return static */
    public function first($expression): self
    {
        $this->expr->first(...func_get_args());

        return $this;
    }

    /** @return static */
    public function firstN($expression, $n): self
    {
        $this->expr->firstN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function floor($number): self
    {
        $this->expr->floor(...func_get_args());

        return $this;
    }

    /** @return static */
    public function getField($field, $input = null): self
    {
        $this->expr->getField(...func_get_args());

        return $this;
    }

    /** @return static */
    public function gt($expression1, $expression2): self
    {
        $this->expr->gt(...func_get_args());

        return $this;
    }

    /** @return static */
    public function gte($expression1, $expression2): self
    {
        $this->expr->gte(...func_get_args());

        return $this;
    }

    /** @return static */
    public function hour($expression): self
    {
        $this->expr->hour(...func_get_args());

        return $this;
    }

    /** @return static */
    public function in($expression, $arrayExpression): self
    {
        $this->expr->in(...func_get_args());

        return $this;
    }

    /** @return static */
    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfArray(...func_get_args());

        return $this;
    }

    /** @return static */
    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfBytes(...func_get_args());

        return $this;
    }

    /** @return static */
    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): self
    {
        $this->expr->indexOfCP(...func_get_args());

        return $this;
    }

    /** @return static */
    public function ifNull($expression, $replacementExpression): self
    {
        $this->expr->ifNull(...func_get_args());

        return $this;
    }

    /** @return static */
    public function isArray($expression): self
    {
        $this->expr->isArray(...func_get_args());

        return $this;
    }

    /** @return static */
    public function isNumber($expression): self
    {
        $this->expr->isNumber(...func_get_args());

        return $this;
    }

    /** @return static */
    public function isoDayOfWeek($expression): self
    {
        $this->expr->isoDayOfWeek(...func_get_args());

        return $this;
    }

    /** @return static */
    public function isoWeek($expression): self
    {
        $this->expr->isoWeek(...func_get_args());

        return $this;
    }

    /** @return static */
    public function isoWeekYear($expression): self
    {
        $this->expr->isoWeekYear(...func_get_args());

        return $this;
    }

    /** @return static */
    public function last($expression): self
    {
        $this->expr->last(...func_get_args());

        return $this;
    }

    /** @return static */
    public function lastN($expression, $n): self
    {
        $this->expr->lastN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function let($vars, $in): self
    {
        $this->expr->let(...func_get_args());

        return $this;
    }

    /** @return static */
    public function literal($value): self
    {
        $this->expr->literal(...func_get_args());

        return $this;
    }

    /** @return static */
    public function ln($number): self
    {
        $this->expr->ln(...func_get_args());

        return $this;
    }

    /** @return static */
    public function log($number, $base): self
    {
        $this->expr->log(...func_get_args());

        return $this;
    }

    /** @return static */
    public function log10($number): self
    {
        $this->expr->log10(...func_get_args());

        return $this;
    }

    /** @return static */
    public function lt($expression1, $expression2): self
    {
        $this->expr->lt(...func_get_args());

        return $this;
    }

    /** @return static */
    public function lte($expression1, $expression2): self
    {
        $this->expr->lte(...func_get_args());

        return $this;
    }

    /** @return static */
    public function ltrim($input, $chars = null): self
    {
        $this->expr->ltrim(...func_get_args());

        return $this;
    }

    /** @return static */
    public function map($input, $as, $in): self
    {
        $this->expr->map(...func_get_args());

        return $this;
    }

    /** @return static */
    public function max($expression, ...$expressions): self
    {
        $this->expr->max(...func_get_args());

        return $this;
    }

    /** @return static */
    public function maxN($expression, $n): self
    {
        $this->expr->maxN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function mergeObjects($expression, ...$expressions): self
    {
        $this->expr->mergeObjects(...func_get_args());

        return $this;
    }

    /** @return static */
    public function meta($metaDataKeyword): self
    {
        $this->expr->meta(...func_get_args());

        return $this;
    }

    /** @return static */
    public function millisecond($expression): self
    {
        $this->expr->millisecond(...func_get_args());

        return $this;
    }

    /** @return static */
    public function min($expression, ...$expressions): self
    {
        $this->expr->min(...func_get_args());

        return $this;
    }

    /** @return static */
    public function minN($expression, $n): self
    {
        $this->expr->minN(...func_get_args());

        return $this;
    }

    /** @return static */
    public function minute($expression): self
    {
        $this->expr->minute(...func_get_args());

        return $this;
    }

    /** @return static */
    public function mod($expression1, $expression2): self
    {
        $this->expr->mod(...func_get_args());

        return $this;
    }

    /** @return static */
    public function month($expression): self
    {
        $this->expr->month(...func_get_args());

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
        $this->expr->ne(...func_get_args());

        return $this;
    }

    /** @return static */
    public function not($expression): self
    {
        $this->expr->not(...func_get_args());

        return $this;
    }

    /** @return static */
    public function objectToArray($object): self
    {
        $this->expr->objectToArray(...func_get_args());

        return $this;
    }

    /** @return static */
    public function or($expression, ...$expressions): self
    {
        $this->expr->or(...func_get_args());

        return $this;
    }

    /** @return static */
    public function pow($number, $exponent): self
    {
        $this->expr->pow(...func_get_args());

        return $this;
    }

    /** @return static */
    public function range($start, $end, $step = 1): self
    {
        $this->expr->range(...func_get_args());

        return $this;
    }

    /** @return static */
    public function reduce($input, $initialValue, $in): self
    {
        $this->expr->reduce(...func_get_args());

        return $this;
    }

    /** @return static */
    public function regexFind($input, $regex, $options = null): self
    {
        $this->expr->regexFind(...func_get_args());

        return $this;
    }

    /** @return static */
    public function regexFindAll($input, $regex, $options = null): self
    {
        $this->expr->regexFindAll(...func_get_args());

        return $this;
    }

    /** @return static */
    public function regexMatch($input, $regex, $options = null): self
    {
        $this->expr->regexMatch(...func_get_args());

        return $this;
    }

    /** @return static */
    public function replaceAll($input, $find, $replacement): self
    {
        $this->expr->replaceAll(...func_get_args());

        return $this;
    }

    /** @return static */
    public function replaceOne($input, $find, $replacement): self
    {
        $this->expr->replaceOne(...func_get_args());

        return $this;
    }

    /** @return static */
    public function reverseArray($expression): self
    {
        $this->expr->reverseArray(...func_get_args());

        return $this;
    }

    /** @return static */
    public function rtrim($input, $chars = null): self
    {
        $this->expr->rtrim(...func_get_args());

        return $this;
    }

    /** @return static */
    public function round($number, $place = null): self
    {
        $this->expr->round(...func_get_args());

        return $this;
    }

    /** @return static */
    public function radiansToDegrees($expression): self
    {
        $this->expr->radiansToDegrees(...func_get_args());

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
        $this->expr->sampleRate(...func_get_args());

        return $this;
    }

    /** @return static */
    public function second($expression): self
    {
        $this->expr->second(...func_get_args());

        return $this;
    }

    /** @return static */
    public function setDifference($expression1, $expression2): self
    {
        $this->expr->setDifference(...func_get_args());

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
        $this->expr->setField(...func_get_args());

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
        $this->expr->setIsSubset(...func_get_args());

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
        $this->expr->sin(...func_get_args());

        return $this;
    }

    /** @return static */
    public function sinh($expression): self
    {
        $this->expr->sinh(...func_get_args());

        return $this;
    }

    /** @return static */
    public function size($expression): self
    {
        $this->expr->size(...func_get_args());

        return $this;
    }

    /** @return static */
    public function slice($array, $n, $position = null): self
    {
        $this->expr->slice(...func_get_args());

        return $this;
    }

    /** @return static */
    public function sortArray($input, $sortBy): self
    {
        $this->expr->sortArray(...func_get_args());

        return $this;
    }

    /** @return static */
    public function split($string, $delimiter): self
    {
        $this->expr->split(...func_get_args());

        return $this;
    }

    /** @return static */
    public function sqrt($expression): self
    {
        $this->expr->sqrt(...func_get_args());

        return $this;
    }

    /** @return static */
    public function stdDevPop($expression, ...$expressions): self
    {
        $this->expr->stdDevPop(...func_get_args());

        return $this;
    }

    /** @return static */
    public function stdDevSamp($expression, ...$expressions): self
    {
        $this->expr->stdDevSamp(...func_get_args());

        return $this;
    }

    /** @return static */
    public function strcasecmp($expression1, $expression2): self
    {
        $this->expr->strcasecmp(...func_get_args());

        return $this;
    }

    /** @return static */
    public function strLenBytes($string): self
    {
        $this->expr->strLenBytes(...func_get_args());

        return $this;
    }

    /** @return static */
    public function strLenCP($string): self
    {
        $this->expr->strLenCP(...func_get_args());

        return $this;
    }

    /** @return static */
    public function substr($string, $start, $length): self
    {
        $this->expr->substr(...func_get_args());

        return $this;
    }

    /** @return static */
    public function substrBytes($string, $start, $count): self
    {
        $this->expr->substrBytes(...func_get_args());

        return $this;
    }

    /** @return static */
    public function substrCP($string, $start, $count): self
    {
        $this->expr->substrCP(...func_get_args());

        return $this;
    }

    /** @return static */
    public function subtract($expression1, $expression2): self
    {
        $this->expr->subtract(...func_get_args());

        return $this;
    }

    /** @return static */
    public function sum($expression, ...$expressions): self
    {
        $this->expr->sum(...func_get_args());

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
        $this->expr->tan(...func_get_args());

        return $this;
    }

    /** @return static */
    public function tanh($expression): self
    {
        $this->expr->tanh(...func_get_args());

        return $this;
    }

    /** @return static */
    public function then($expression): self
    {
        $this->expr->then(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toBool($expression): self
    {
        $this->expr->toBool(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toDate($expression): self
    {
        $this->expr->toDate(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toDecimal($expression): self
    {
        $this->expr->toDecimal(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toDouble($expression): self
    {
        $this->expr->toDouble(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toInt($expression): self
    {
        $this->expr->toInt(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toLong($expression): self
    {
        $this->expr->toLong(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toObjectId($expression): self
    {
        $this->expr->toObjectId(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toString($expression): self
    {
        $this->expr->toString(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toLower($expression): self
    {
        $this->expr->toLower(...func_get_args());

        return $this;
    }

    /** @return static */
    public function toUpper($expression): self
    {
        $this->expr->toUpper(...func_get_args());

        return $this;
    }

    /** @return static */
    public function trim($input, $chars = null): self
    {
        $this->expr->trim(...func_get_args());

        return $this;
    }

    /** @return static */
    public function trunc($number): self
    {
        $this->expr->trunc(...func_get_args());

        return $this;
    }

    /** @return static */
    public function tsIncrement($expression): self
    {
        $this->expr->tsIncrement(...func_get_args());

        return $this;
    }

    /** @return static */
    public function tsSecond($expression): self
    {
        $this->expr->tsSecond(...func_get_args());

        return $this;
    }

    /** @return static */
    public function type($expression): self
    {
        $this->expr->type(...func_get_args());

        return $this;
    }

    /** @return static */
    public function week($expression): self
    {
        $this->expr->week(...func_get_args());

        return $this;
    }

    /** @return static */
    public function year($expression): self
    {
        $this->expr->year(...func_get_args());

        return $this;
    }

    /** @return static */
    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): self
    {
        $this->expr->zip(...func_get_args());

        return $this;
    }
}
