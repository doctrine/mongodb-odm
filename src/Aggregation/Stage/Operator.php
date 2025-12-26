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
    protected Expr $expr;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->expr = $builder->expr();
    }

    public function abs(mixed $number): static
    {
        $this->expr->abs(...func_get_args());

        return $this;
    }

    public function acos(mixed $expression): static
    {
        $this->expr->acos(...func_get_args());

        return $this;
    }

    public function acosh(mixed $expression): static
    {
        $this->expr->acosh(...func_get_args());

        return $this;
    }

    public function add(mixed $expression1, mixed $expression2, mixed ...$expressions): static
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
    public function addAnd(array|Expr $expression, array|Expr ...$expressions): static
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
    public function addOr(array|Expr $expression, array|Expr ...$expressions): static
    {
        $this->expr->addOr(...func_get_args());

        return $this;
    }

    public function allElementsTrue(mixed $expression): static
    {
        $this->expr->allElementsTrue(...func_get_args());

        return $this;
    }

    public function and(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->and(...func_get_args());

        return $this;
    }

    public function anyElementTrue(mixed $expression): static
    {
        $this->expr->anyElementTrue(...func_get_args());

        return $this;
    }

    public function arrayElemAt(mixed $array, mixed $index): static
    {
        $this->expr->arrayElemAt(...func_get_args());

        return $this;
    }

    public function arrayToObject(mixed $array): static
    {
        $this->expr->arrayToObject(...func_get_args());

        return $this;
    }

    public function atan(mixed $expression): static
    {
        $this->expr->atan(...func_get_args());

        return $this;
    }

    public function asin(mixed $expression): static
    {
        $this->expr->asin(...func_get_args());

        return $this;
    }

    public function asinh(mixed $expression): static
    {
        $this->expr->asinh(...func_get_args());

        return $this;
    }

    public function atan2(mixed $expression1, mixed $expression2): static
    {
        $this->expr->atan2(...func_get_args());

        return $this;
    }

    public function atanh(mixed $expression): static
    {
        $this->expr->atanh(...func_get_args());

        return $this;
    }

    public function avg(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->avg(...func_get_args());

        return $this;
    }

    public function binarySize(mixed $expression): static
    {
        $this->expr->binarySize(...func_get_args());

        return $this;
    }

    public function bsonSize(mixed $expression): static
    {
        $this->expr->bsonSize(...func_get_args());

        return $this;
    }

    public function case(mixed $expression): static
    {
        $this->expr->case(...func_get_args());

        return $this;
    }

    public function ceil(mixed $number): static
    {
        $this->expr->ceil(...func_get_args());

        return $this;
    }

    public function cmp(mixed $expression1, mixed $expression2): static
    {
        $this->expr->cmp(...func_get_args());

        return $this;
    }

    public function concat(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        $this->expr->concat(...func_get_args());

        return $this;
    }

    public function concatArrays(mixed $array1, mixed $array2, mixed ...$arrays): static
    {
        $this->expr->concatArrays(...func_get_args());

        return $this;
    }

    public function cond(mixed $if, mixed $then, mixed $else): static
    {
        $this->expr->cond(...func_get_args());

        return $this;
    }

    public function convert(mixed $input, mixed $to, mixed $onError = null, mixed $onNull = null): static
    {
        $this->expr->convert(...func_get_args());

        return $this;
    }

    public function cos(mixed $expression): static
    {
        $this->expr->cos(...func_get_args());

        return $this;
    }

    public function cosh(mixed $expression): static
    {
        $this->expr->cosh(...func_get_args());

        return $this;
    }

    public function dateAdd(mixed $startDate, mixed $unit, mixed $amount, mixed $timezone = null): static
    {
        $this->expr->dateAdd(...func_get_args());

        return $this;
    }

    public function dateDiff(mixed $startDate, mixed $endDate, mixed $unit, mixed $timezone = null, mixed $startOfWeek = null): static
    {
        $this->expr->dateDiff(...func_get_args());

        return $this;
    }

    public function dateFromParts(mixed $year = null, mixed $isoWeekYear = null, mixed $month = null, mixed $isoWeek = null, mixed $day = null, mixed $isoDayOfWeek = null, mixed $hour = null, mixed $minute = null, mixed $second = null, mixed $millisecond = null, mixed $timezone = null): static
    {
        $this->expr->dateFromParts(...func_get_args());

        return $this;
    }

    public function dateFromString(mixed $dateString, mixed $format = null, mixed $timezone = null, mixed $onError = null, mixed $onNull = null): static
    {
        $this->expr->dateFromString(...func_get_args());

        return $this;
    }

    public function dateSubtract(mixed $startDate, mixed $unit, mixed $amount, mixed $timezone = null): static
    {
        $this->expr->dateSubtract(...func_get_args());

        return $this;
    }

    public function dateToParts(mixed $date, mixed $timezone = null, mixed $iso8601 = null): static
    {
        $this->expr->dateToParts(...func_get_args());

        return $this;
    }

    public function dateToString(string $format, mixed $expression, mixed $timezone = null, mixed $onNull = null): static
    {
        $this->expr->dateToString(...func_get_args());

        return $this;
    }

    public function dateTrunc(mixed $date, mixed $unit, mixed $binSize = null, mixed $timezone = null, mixed $startOfWeek = null): static
    {
        $this->expr->dateTrunc(...func_get_args());

        return $this;
    }

    public function dayOfMonth(mixed $expression): static
    {
        $this->expr->dayOfMonth(...func_get_args());

        return $this;
    }

    public function dayOfWeek(mixed $expression): static
    {
        $this->expr->dayOfWeek(...func_get_args());

        return $this;
    }

    public function dayOfYear(mixed $expression): static
    {
        $this->expr->dayOfYear(...func_get_args());

        return $this;
    }

    public function default(mixed $expression): static
    {
        $this->expr->default(...func_get_args());

        return $this;
    }

    public function degreesToRadians(mixed $expression): static
    {
        $this->expr->degreesToRadians(...func_get_args());

        return $this;
    }

    public function divide(mixed $expression1, mixed $expression2): static
    {
        $this->expr->divide(...func_get_args());

        return $this;
    }

    public function eq(mixed $expression1, mixed $expression2): static
    {
        $this->expr->eq(...func_get_args());

        return $this;
    }

    public function exp(mixed $exponent): static
    {
        $this->expr->exp(...func_get_args());

        return $this;
    }

    public function expression(mixed $value): static
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

    public function filter(mixed $input, mixed $as, mixed $cond): static
    {
        $this->expr->filter(...func_get_args());

        return $this;
    }

    public function first(mixed $expression): static
    {
        $this->expr->first(...func_get_args());

        return $this;
    }

    public function firstN(mixed $expression, mixed $n): static
    {
        $this->expr->firstN(...func_get_args());

        return $this;
    }

    public function floor(mixed $number): static
    {
        $this->expr->floor(...func_get_args());

        return $this;
    }

    public function getField(mixed $field, mixed $input = null): static
    {
        $this->expr->getField(...func_get_args());

        return $this;
    }

    public function gt(mixed $expression1, mixed $expression2): static
    {
        $this->expr->gt(...func_get_args());

        return $this;
    }

    public function gte(mixed $expression1, mixed $expression2): static
    {
        $this->expr->gte(...func_get_args());

        return $this;
    }

    public function hour(mixed $expression): static
    {
        $this->expr->hour(...func_get_args());

        return $this;
    }

    public function in(mixed $expression, mixed $arrayExpression): static
    {
        $this->expr->in(...func_get_args());

        return $this;
    }

    public function indexOfArray(mixed $arrayExpression, mixed $searchExpression, mixed $start = null, mixed $end = null): static
    {
        $this->expr->indexOfArray(...func_get_args());

        return $this;
    }

    public function indexOfBytes(mixed $stringExpression, mixed $substringExpression, string|int|null $start = null, string|int|null $end = null): static
    {
        $this->expr->indexOfBytes(...func_get_args());

        return $this;
    }

    public function indexOfCP(mixed $stringExpression, mixed $substringExpression, string|int|null $start = null, string|int|null $end = null): static
    {
        $this->expr->indexOfCP(...func_get_args());

        return $this;
    }

    public function ifNull(mixed $expression, mixed $replacementExpression): static
    {
        $this->expr->ifNull(...func_get_args());

        return $this;
    }

    public function isArray(mixed $expression): static
    {
        $this->expr->isArray(...func_get_args());

        return $this;
    }

    public function isNumber(mixed $expression): static
    {
        $this->expr->isNumber(...func_get_args());

        return $this;
    }

    public function isoDayOfWeek(mixed $expression): static
    {
        $this->expr->isoDayOfWeek(...func_get_args());

        return $this;
    }

    public function isoWeek(mixed $expression): static
    {
        $this->expr->isoWeek(...func_get_args());

        return $this;
    }

    public function isoWeekYear(mixed $expression): static
    {
        $this->expr->isoWeekYear(...func_get_args());

        return $this;
    }

    public function last(mixed $expression): static
    {
        $this->expr->last(...func_get_args());

        return $this;
    }

    public function lastN(mixed $expression, mixed $n): static
    {
        $this->expr->lastN(...func_get_args());

        return $this;
    }

    public function let(mixed $vars, mixed $in): static
    {
        $this->expr->let(...func_get_args());

        return $this;
    }

    public function literal(mixed $value): static
    {
        $this->expr->literal(...func_get_args());

        return $this;
    }

    public function ln(mixed $number): static
    {
        $this->expr->ln(...func_get_args());

        return $this;
    }

    public function log(mixed $number, mixed $base): static
    {
        $this->expr->log(...func_get_args());

        return $this;
    }

    public function log10(mixed $number): static
    {
        $this->expr->log10(...func_get_args());

        return $this;
    }

    public function lt(mixed $expression1, mixed $expression2): static
    {
        $this->expr->lt(...func_get_args());

        return $this;
    }

    public function lte(mixed $expression1, mixed $expression2): static
    {
        $this->expr->lte(...func_get_args());

        return $this;
    }

    public function ltrim(mixed $input, mixed $chars = null): static
    {
        $this->expr->ltrim(...func_get_args());

        return $this;
    }

    public function map(mixed $input, string $as, mixed $in): static
    {
        $this->expr->map(...func_get_args());

        return $this;
    }

    public function max(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->max(...func_get_args());

        return $this;
    }

    public function maxN(mixed $expression, mixed $n): static
    {
        $this->expr->maxN(...func_get_args());

        return $this;
    }

    public function mergeObjects(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->mergeObjects(...func_get_args());

        return $this;
    }

    public function meta(mixed $metaDataKeyword): static
    {
        $this->expr->meta(...func_get_args());

        return $this;
    }

    public function millisecond(mixed $expression): static
    {
        $this->expr->millisecond(...func_get_args());

        return $this;
    }

    public function min(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->min(...func_get_args());

        return $this;
    }

    public function minN(mixed $expression, mixed $n): static
    {
        $this->expr->minN(...func_get_args());

        return $this;
    }

    public function minute(mixed $expression): static
    {
        $this->expr->minute(...func_get_args());

        return $this;
    }

    public function mod(mixed $expression1, mixed $expression2): static
    {
        $this->expr->mod(...func_get_args());

        return $this;
    }

    public function month(mixed $expression): static
    {
        $this->expr->month(...func_get_args());

        return $this;
    }

    public function multiply(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        $this->expr->multiply(...func_get_args());

        return $this;
    }

    public function ne(mixed $expression1, mixed $expression2): static
    {
        $this->expr->ne(...func_get_args());

        return $this;
    }

    public function not(mixed $expression): static
    {
        $this->expr->not(...func_get_args());

        return $this;
    }

    public function objectToArray(mixed $object): static
    {
        $this->expr->objectToArray(...func_get_args());

        return $this;
    }

    public function or(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->or(...func_get_args());

        return $this;
    }

    public function pow(mixed $number, mixed $exponent): static
    {
        $this->expr->pow(...func_get_args());

        return $this;
    }

    public function range(mixed $start, mixed $end, mixed $step = null): static
    {
        $this->expr->range(...func_get_args());

        return $this;
    }

    public function reduce(mixed $input, mixed $initialValue, mixed $in): static
    {
        $this->expr->reduce(...func_get_args());

        return $this;
    }

    public function regexFind(mixed $input, mixed $regex, ?string $options = null): static
    {
        $this->expr->regexFind(...func_get_args());

        return $this;
    }

    public function regexFindAll(mixed $input, mixed $regex, ?string $options = null): static
    {
        $this->expr->regexFindAll(...func_get_args());

        return $this;
    }

    public function regexMatch(mixed $input, mixed $regex, ?string $options = null): static
    {
        $this->expr->regexMatch(...func_get_args());

        return $this;
    }

    public function replaceAll(mixed $input, mixed $find, mixed $replacement): static
    {
        $this->expr->replaceAll(...func_get_args());

        return $this;
    }

    public function replaceOne(mixed $input, mixed $find, mixed $replacement): static
    {
        $this->expr->replaceOne(...func_get_args());

        return $this;
    }

    public function reverseArray(mixed $expression): static
    {
        $this->expr->reverseArray(...func_get_args());

        return $this;
    }

    public function rtrim(mixed $input, mixed $chars = null): static
    {
        $this->expr->rtrim(...func_get_args());

        return $this;
    }

    public function round(mixed $number, mixed $place = null): static
    {
        $this->expr->round(...func_get_args());

        return $this;
    }

    public function radiansToDegrees(mixed $expression): static
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

    public function second(mixed $expression): static
    {
        $this->expr->second(...func_get_args());

        return $this;
    }

    public function setDifference(mixed $expression1, mixed $expression2): static
    {
        $this->expr->setDifference(...func_get_args());

        return $this;
    }

    public function setEquals(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        $this->expr->setEquals(...func_get_args());

        return $this;
    }

    public function setField(mixed $field, mixed $input, mixed $value): static
    {
        $this->expr->setField(...func_get_args());

        return $this;
    }

    public function setIntersection(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        $this->expr->setIntersection(...func_get_args());

        return $this;
    }

    public function setIsSubset(mixed $expression1, mixed $expression2): static
    {
        $this->expr->setIsSubset(...func_get_args());

        return $this;
    }

    public function setUnion(mixed $expression1, mixed $expression2, mixed ...$expressions): static
    {
        $this->expr->setUnion(...func_get_args());

        return $this;
    }

    public function sin(mixed $expression): static
    {
        $this->expr->sin(...func_get_args());

        return $this;
    }

    public function sinh(mixed $expression): static
    {
        $this->expr->sinh(...func_get_args());

        return $this;
    }

    public function size(mixed $expression): static
    {
        $this->expr->size(...func_get_args());

        return $this;
    }

    public function slice(mixed $array, mixed $n, mixed $position = null): static
    {
        $this->expr->slice(...func_get_args());

        return $this;
    }

    public function sortArray(mixed $input, mixed $sortBy): static
    {
        $this->expr->sortArray(...func_get_args());

        return $this;
    }

    public function split(mixed $string, mixed $delimiter): static
    {
        $this->expr->split(...func_get_args());

        return $this;
    }

    public function sqrt(mixed $expression): static
    {
        $this->expr->sqrt(...func_get_args());

        return $this;
    }

    public function stdDevPop(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->stdDevPop(...func_get_args());

        return $this;
    }

    public function stdDevSamp(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->stdDevSamp(...func_get_args());

        return $this;
    }

    public function strcasecmp(mixed $expression1, mixed $expression2): static
    {
        $this->expr->strcasecmp(...func_get_args());

        return $this;
    }

    public function strLenBytes(mixed $string): static
    {
        $this->expr->strLenBytes(...func_get_args());

        return $this;
    }

    public function strLenCP(mixed $string): static
    {
        $this->expr->strLenCP(...func_get_args());

        return $this;
    }

    public function substr(mixed $string, mixed $start, mixed $length): static
    {
        $this->expr->substr(...func_get_args());

        return $this;
    }

    public function substrBytes(mixed $string, mixed $start, mixed $count): static
    {
        $this->expr->substrBytes(...func_get_args());

        return $this;
    }

    public function substrCP(mixed $string, mixed $start, mixed $count): static
    {
        $this->expr->substrCP(...func_get_args());

        return $this;
    }

    public function subtract(mixed $expression1, mixed $expression2): static
    {
        $this->expr->subtract(...func_get_args());

        return $this;
    }

    public function sum(mixed $expression, mixed ...$expressions): static
    {
        $this->expr->sum(...func_get_args());

        return $this;
    }

    public function switch(): static
    {
        $this->expr->switch();

        return $this;
    }

    public function tan(mixed $expression): static
    {
        $this->expr->tan(...func_get_args());

        return $this;
    }

    public function tanh(mixed $expression): static
    {
        $this->expr->tanh(...func_get_args());

        return $this;
    }

    public function then(mixed $expression): static
    {
        $this->expr->then(...func_get_args());

        return $this;
    }

    public function toBool(mixed $expression): static
    {
        $this->expr->toBool(...func_get_args());

        return $this;
    }

    public function toDate(mixed $expression): static
    {
        $this->expr->toDate(...func_get_args());

        return $this;
    }

    public function toDecimal(mixed $expression): static
    {
        $this->expr->toDecimal(...func_get_args());

        return $this;
    }

    public function toDouble(mixed $expression): static
    {
        $this->expr->toDouble(...func_get_args());

        return $this;
    }

    public function toInt(mixed $expression): static
    {
        $this->expr->toInt(...func_get_args());

        return $this;
    }

    public function toLong(mixed $expression): static
    {
        $this->expr->toLong(...func_get_args());

        return $this;
    }

    public function toObjectId(mixed $expression): static
    {
        $this->expr->toObjectId(...func_get_args());

        return $this;
    }

    public function toString(mixed $expression): static
    {
        $this->expr->toString(...func_get_args());

        return $this;
    }

    public function toLower(mixed $expression): static
    {
        $this->expr->toLower(...func_get_args());

        return $this;
    }

    public function toUpper(mixed $expression): static
    {
        $this->expr->toUpper(...func_get_args());

        return $this;
    }

    public function trim(mixed $input, mixed $chars = null): static
    {
        $this->expr->trim(...func_get_args());

        return $this;
    }

    public function trunc(mixed $number): static
    {
        $this->expr->trunc(...func_get_args());

        return $this;
    }

    public function tsIncrement(mixed $expression): static
    {
        $this->expr->tsIncrement(...func_get_args());

        return $this;
    }

    public function tsSecond(mixed $expression): static
    {
        $this->expr->tsSecond(...func_get_args());

        return $this;
    }

    public function type(mixed $expression): static
    {
        $this->expr->type(...func_get_args());

        return $this;
    }

    public function week(mixed $expression): static
    {
        $this->expr->week(...func_get_args());

        return $this;
    }

    public function year(mixed $expression): static
    {
        $this->expr->year(...func_get_args());

        return $this;
    }

    public function zip(mixed $inputs, ?bool $useLongestLength = null, mixed $defaults = null): static
    {
        $this->expr->zip(...func_get_args());

        return $this;
    }
}
