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

    public function abs($number): static
    {
        $this->expr->abs(...func_get_args());

        return $this;
    }

    public function acos($expression): static
    {
        $this->expr->acos(...func_get_args());

        return $this;
    }

    public function acosh($expression): static
    {
        $this->expr->acosh(...func_get_args());

        return $this;
    }

    public function add($expression1, $expression2, ...$expressions): static
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
     */
    public function addAnd($expression, ...$expressions): static
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
     */
    public function addOr($expression, ...$expressions): static
    {
        $this->expr->addOr(...func_get_args());

        return $this;
    }

    public function allElementsTrue($expression): static
    {
        $this->expr->allElementsTrue(...func_get_args());

        return $this;
    }

    public function and($expression, ...$expressions): static
    {
        $this->expr->and(...func_get_args());

        return $this;
    }

    public function anyElementTrue($expression): static
    {
        $this->expr->anyElementTrue(...func_get_args());

        return $this;
    }

    public function arrayElemAt($array, $index): static
    {
        $this->expr->arrayElemAt(...func_get_args());

        return $this;
    }

    public function arrayToObject($array): static
    {
        $this->expr->arrayToObject(...func_get_args());

        return $this;
    }

    public function atan($expression): static
    {
        $this->expr->atan(...func_get_args());

        return $this;
    }

    public function asin($expression): static
    {
        $this->expr->asin(...func_get_args());

        return $this;
    }

    public function asinh($expression): static
    {
        $this->expr->asinh(...func_get_args());

        return $this;
    }

    public function atan2($expression1, $expression2): static
    {
        $this->expr->atan2(...func_get_args());

        return $this;
    }

    public function atanh($expression): static
    {
        $this->expr->atanh(...func_get_args());

        return $this;
    }

    public function avg($expression, ...$expressions): static
    {
        $this->expr->avg(...func_get_args());

        return $this;
    }

    public function binarySize($expression): static
    {
        $this->expr->binarySize(...func_get_args());

        return $this;
    }

    public function bsonSize($expression): static
    {
        $this->expr->bsonSize(...func_get_args());

        return $this;
    }

    public function case($expression): static
    {
        $this->expr->case(...func_get_args());

        return $this;
    }

    public function ceil($number): static
    {
        $this->expr->ceil(...func_get_args());

        return $this;
    }

    public function cmp($expression1, $expression2): static
    {
        $this->expr->cmp(...func_get_args());

        return $this;
    }

    public function concat($expression1, $expression2, ...$expressions): static
    {
        $this->expr->concat(...func_get_args());

        return $this;
    }

    public function concatArrays($array1, $array2, ...$arrays): static
    {
        $this->expr->concatArrays(...func_get_args());

        return $this;
    }

    public function cond($if, $then, $else): static
    {
        $this->expr->cond(...func_get_args());

        return $this;
    }

    public function convert($input, $to, $onError = null, $onNull = null): static
    {
        $this->expr->convert(...func_get_args());

        return $this;
    }

    public function cos($expression): static
    {
        $this->expr->cos(...func_get_args());

        return $this;
    }

    public function cosh($expression): static
    {
        $this->expr->cosh(...func_get_args());

        return $this;
    }

    public function dateAdd($startDate, $unit, $amount, $timezone = null): static
    {
        $this->expr->dateAdd(...func_get_args());

        return $this;
    }

    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): static
    {
        $this->expr->dateDiff(...func_get_args());

        return $this;
    }

    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): static
    {
        $this->expr->dateFromParts(...func_get_args());

        return $this;
    }

    public function dateFromString($dateString, $format = null, $timezone = null, $onError = null, $onNull = null): static
    {
        $this->expr->dateFromString(...func_get_args());

        return $this;
    }

    public function dateSubtract($startDate, $unit, $amount, $timezone = null): static
    {
        $this->expr->dateSubtract(...func_get_args());

        return $this;
    }

    public function dateToParts($date, $timezone = null, $iso8601 = null): static
    {
        $this->expr->dateToParts(...func_get_args());

        return $this;
    }

    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): static
    {
        $this->expr->dateToString(...func_get_args());

        return $this;
    }

    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): static
    {
        $this->expr->dateTrunc(...func_get_args());

        return $this;
    }

    public function dayOfMonth($expression): static
    {
        $this->expr->dayOfMonth(...func_get_args());

        return $this;
    }

    public function dayOfWeek($expression): static
    {
        $this->expr->dayOfWeek(...func_get_args());

        return $this;
    }

    public function dayOfYear($expression): static
    {
        $this->expr->dayOfYear(...func_get_args());

        return $this;
    }

    public function default($expression): static
    {
        $this->expr->default(...func_get_args());

        return $this;
    }

    public function degreesToRadians($expression): static
    {
        $this->expr->degreesToRadians(...func_get_args());

        return $this;
    }

    public function divide($expression1, $expression2): static
    {
        $this->expr->divide(...func_get_args());

        return $this;
    }

    public function eq($expression1, $expression2): static
    {
        $this->expr->eq(...func_get_args());

        return $this;
    }

    public function exp($exponent): static
    {
        $this->expr->exp(...func_get_args());

        return $this;
    }

    public function expression($value): static
    {
        $this->expr->expression(...func_get_args());

        return $this;
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Expr::field
     */
    public function field(string $fieldName): static
    {
        $this->expr->field(...func_get_args());

        return $this;
    }

    public function filter($input, $as, $cond): static
    {
        $this->expr->filter(...func_get_args());

        return $this;
    }

    public function first($expression): static
    {
        $this->expr->first(...func_get_args());

        return $this;
    }

    public function firstN($expression, $n): static
    {
        $this->expr->firstN(...func_get_args());

        return $this;
    }

    public function floor($number): static
    {
        $this->expr->floor(...func_get_args());

        return $this;
    }

    public function getField($field, $input = null): static
    {
        $this->expr->getField(...func_get_args());

        return $this;
    }

    public function gt($expression1, $expression2): static
    {
        $this->expr->gt(...func_get_args());

        return $this;
    }

    public function gte($expression1, $expression2): static
    {
        $this->expr->gte(...func_get_args());

        return $this;
    }

    public function hour($expression): static
    {
        $this->expr->hour(...func_get_args());

        return $this;
    }

    public function in($expression, $arrayExpression): static
    {
        $this->expr->in(...func_get_args());

        return $this;
    }

    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null): static
    {
        $this->expr->indexOfArray(...func_get_args());

        return $this;
    }

    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): static
    {
        $this->expr->indexOfBytes(...func_get_args());

        return $this;
    }

    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): static
    {
        $this->expr->indexOfCP(...func_get_args());

        return $this;
    }

    public function ifNull($expression, $replacementExpression): static
    {
        $this->expr->ifNull(...func_get_args());

        return $this;
    }

    public function isArray($expression): static
    {
        $this->expr->isArray(...func_get_args());

        return $this;
    }

    public function isNumber($expression): static
    {
        $this->expr->isNumber(...func_get_args());

        return $this;
    }

    public function isoDayOfWeek($expression): static
    {
        $this->expr->isoDayOfWeek(...func_get_args());

        return $this;
    }

    public function isoWeek($expression): static
    {
        $this->expr->isoWeek(...func_get_args());

        return $this;
    }

    public function isoWeekYear($expression): static
    {
        $this->expr->isoWeekYear(...func_get_args());

        return $this;
    }

    public function last($expression): static
    {
        $this->expr->last(...func_get_args());

        return $this;
    }

    public function lastN($expression, $n): static
    {
        $this->expr->lastN(...func_get_args());

        return $this;
    }

    public function let($vars, $in): static
    {
        $this->expr->let(...func_get_args());

        return $this;
    }

    public function literal($value): static
    {
        $this->expr->literal(...func_get_args());

        return $this;
    }

    public function ln($number): static
    {
        $this->expr->ln(...func_get_args());

        return $this;
    }

    public function log($number, $base): static
    {
        $this->expr->log(...func_get_args());

        return $this;
    }

    public function log10($number): static
    {
        $this->expr->log10(...func_get_args());

        return $this;
    }

    public function lt($expression1, $expression2): static
    {
        $this->expr->lt(...func_get_args());

        return $this;
    }

    public function lte($expression1, $expression2): static
    {
        $this->expr->lte(...func_get_args());

        return $this;
    }

    public function ltrim($input, $chars = null): static
    {
        $this->expr->ltrim(...func_get_args());

        return $this;
    }

    public function map($input, $as, $in): static
    {
        $this->expr->map(...func_get_args());

        return $this;
    }

    public function max($expression, ...$expressions): static
    {
        $this->expr->max(...func_get_args());

        return $this;
    }

    public function maxN($expression, $n): static
    {
        $this->expr->maxN(...func_get_args());

        return $this;
    }

    public function mergeObjects($expression, ...$expressions): static
    {
        $this->expr->mergeObjects(...func_get_args());

        return $this;
    }

    public function meta($metaDataKeyword): static
    {
        $this->expr->meta(...func_get_args());

        return $this;
    }

    public function millisecond($expression): static
    {
        $this->expr->millisecond(...func_get_args());

        return $this;
    }

    public function min($expression, ...$expressions): static
    {
        $this->expr->min(...func_get_args());

        return $this;
    }

    public function minN($expression, $n): static
    {
        $this->expr->minN(...func_get_args());

        return $this;
    }

    public function minute($expression): static
    {
        $this->expr->minute(...func_get_args());

        return $this;
    }

    public function mod($expression1, $expression2): static
    {
        $this->expr->mod(...func_get_args());

        return $this;
    }

    public function month($expression): static
    {
        $this->expr->month(...func_get_args());

        return $this;
    }

    public function multiply($expression1, $expression2, ...$expressions): static
    {
        $this->expr->multiply(...func_get_args());

        return $this;
    }

    public function ne($expression1, $expression2): static
    {
        $this->expr->ne(...func_get_args());

        return $this;
    }

    public function not($expression): static
    {
        $this->expr->not(...func_get_args());

        return $this;
    }

    public function objectToArray($object): static
    {
        $this->expr->objectToArray(...func_get_args());

        return $this;
    }

    public function or($expression, ...$expressions): static
    {
        $this->expr->or(...func_get_args());

        return $this;
    }

    public function pow($number, $exponent): static
    {
        $this->expr->pow(...func_get_args());

        return $this;
    }

    public function range($start, $end, $step = null): static
    {
        $this->expr->range(...func_get_args());

        return $this;
    }

    public function reduce($input, $initialValue, $in): static
    {
        $this->expr->reduce(...func_get_args());

        return $this;
    }

    public function regexFind($input, $regex, $options = null): static
    {
        $this->expr->regexFind(...func_get_args());

        return $this;
    }

    public function regexFindAll($input, $regex, $options = null): static
    {
        $this->expr->regexFindAll(...func_get_args());

        return $this;
    }

    public function regexMatch($input, $regex, $options = null): static
    {
        $this->expr->regexMatch(...func_get_args());

        return $this;
    }

    public function replaceAll($input, $find, $replacement): static
    {
        $this->expr->replaceAll(...func_get_args());

        return $this;
    }

    public function replaceOne($input, $find, $replacement): static
    {
        $this->expr->replaceOne(...func_get_args());

        return $this;
    }

    public function reverseArray($expression): static
    {
        $this->expr->reverseArray(...func_get_args());

        return $this;
    }

    public function rtrim($input, $chars = null): static
    {
        $this->expr->rtrim(...func_get_args());

        return $this;
    }

    public function round($number, $place = null): static
    {
        $this->expr->round(...func_get_args());

        return $this;
    }

    public function radiansToDegrees($expression): static
    {
        $this->expr->radiansToDegrees(...func_get_args());

        return $this;
    }

    public function rand(): static
    {
        $this->expr->rand();

        return $this;
    }

    public function sampleRate(float $rate): static
    {
        $this->expr->sampleRate(...func_get_args());

        return $this;
    }

    public function second($expression): static
    {
        $this->expr->second(...func_get_args());

        return $this;
    }

    public function setDifference($expression1, $expression2): static
    {
        $this->expr->setDifference(...func_get_args());

        return $this;
    }

    public function setEquals($expression1, $expression2, ...$expressions): static
    {
        $this->expr->setEquals(...func_get_args());

        return $this;
    }

    public function setField($field, $input, $value): static
    {
        $this->expr->setField(...func_get_args());

        return $this;
    }

    public function setIntersection($expression1, $expression2, ...$expressions): static
    {
        $this->expr->setIntersection(...func_get_args());

        return $this;
    }

    public function setIsSubset($expression1, $expression2): static
    {
        $this->expr->setIsSubset(...func_get_args());

        return $this;
    }

    public function setUnion($expression1, $expression2, ...$expressions): static
    {
        $this->expr->setUnion(...func_get_args());

        return $this;
    }

    public function sin($expression): static
    {
        $this->expr->sin(...func_get_args());

        return $this;
    }

    public function sinh($expression): static
    {
        $this->expr->sinh(...func_get_args());

        return $this;
    }

    public function size($expression): static
    {
        $this->expr->size(...func_get_args());

        return $this;
    }

    public function slice($array, $n, $position = null): static
    {
        $this->expr->slice(...func_get_args());

        return $this;
    }

    public function sortArray($input, $sortBy): static
    {
        $this->expr->sortArray(...func_get_args());

        return $this;
    }

    public function split($string, $delimiter): static
    {
        $this->expr->split(...func_get_args());

        return $this;
    }

    public function sqrt($expression): static
    {
        $this->expr->sqrt(...func_get_args());

        return $this;
    }

    public function stdDevPop($expression, ...$expressions): static
    {
        $this->expr->stdDevPop(...func_get_args());

        return $this;
    }

    public function stdDevSamp($expression, ...$expressions): static
    {
        $this->expr->stdDevSamp(...func_get_args());

        return $this;
    }

    public function strcasecmp($expression1, $expression2): static
    {
        $this->expr->strcasecmp(...func_get_args());

        return $this;
    }

    public function strLenBytes($string): static
    {
        $this->expr->strLenBytes(...func_get_args());

        return $this;
    }

    public function strLenCP($string): static
    {
        $this->expr->strLenCP(...func_get_args());

        return $this;
    }

    public function substr($string, $start, $length): static
    {
        $this->expr->substr(...func_get_args());

        return $this;
    }

    public function substrBytes($string, $start, $count): static
    {
        $this->expr->substrBytes(...func_get_args());

        return $this;
    }

    public function substrCP($string, $start, $count): static
    {
        $this->expr->substrCP(...func_get_args());

        return $this;
    }

    public function subtract($expression1, $expression2): static
    {
        $this->expr->subtract(...func_get_args());

        return $this;
    }

    public function sum($expression, ...$expressions): static
    {
        $this->expr->sum(...func_get_args());

        return $this;
    }

    public function switch(): static
    {
        $this->expr->switch();

        return $this;
    }

    public function tan($expression): static
    {
        $this->expr->tan(...func_get_args());

        return $this;
    }

    public function tanh($expression): static
    {
        $this->expr->tanh(...func_get_args());

        return $this;
    }

    public function then($expression): static
    {
        $this->expr->then(...func_get_args());

        return $this;
    }

    public function toBool($expression): static
    {
        $this->expr->toBool(...func_get_args());

        return $this;
    }

    public function toDate($expression): static
    {
        $this->expr->toDate(...func_get_args());

        return $this;
    }

    public function toDecimal($expression): static
    {
        $this->expr->toDecimal(...func_get_args());

        return $this;
    }

    public function toDouble($expression): static
    {
        $this->expr->toDouble(...func_get_args());

        return $this;
    }

    public function toInt($expression): static
    {
        $this->expr->toInt(...func_get_args());

        return $this;
    }

    public function toLong($expression): static
    {
        $this->expr->toLong(...func_get_args());

        return $this;
    }

    public function toObjectId($expression): static
    {
        $this->expr->toObjectId(...func_get_args());

        return $this;
    }

    public function toString($expression): static
    {
        $this->expr->toString(...func_get_args());

        return $this;
    }

    public function toLower($expression): static
    {
        $this->expr->toLower(...func_get_args());

        return $this;
    }

    public function toUpper($expression): static
    {
        $this->expr->toUpper(...func_get_args());

        return $this;
    }

    public function trim($input, $chars = null): static
    {
        $this->expr->trim(...func_get_args());

        return $this;
    }

    public function trunc($number): static
    {
        $this->expr->trunc(...func_get_args());

        return $this;
    }

    public function tsIncrement($expression): static
    {
        $this->expr->tsIncrement(...func_get_args());

        return $this;
    }

    public function tsSecond($expression): static
    {
        $this->expr->tsSecond(...func_get_args());

        return $this;
    }

    public function type($expression): static
    {
        $this->expr->type(...func_get_args());

        return $this;
    }

    public function week($expression): static
    {
        $this->expr->week(...func_get_args());

        return $this;
    }

    public function year($expression): static
    {
        $this->expr->year(...func_get_args());

        return $this;
    }

    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): static
    {
        $this->expr->zip(...func_get_args());

        return $this;
    }
}
