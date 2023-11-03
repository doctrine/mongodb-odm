<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all date aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface DateOperators
{
    /**
     * Increments a date object by a specified number of time units
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateAdd/
     *
     * @param mixed|Expr $startDate
     * @param mixed|Expr $unit
     * @param mixed|Expr $amount
     * @param mixed|Expr $timezone
     */
    public function dateAdd($startDate, $unit, $amount, $timezone = null): static;

    /**
     * Returns the difference between two dates
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateDiff/
     *
     * @param mixed|Expr $startDate
     * @param mixed|Expr $endDate
     * @param mixed|Expr $unit
     * @param mixed|Expr $timezone
     * @param mixed|Expr $startOfWeek
     */
    public function dateDiff($startDate, $endDate, $unit, $timezone = null, $startOfWeek = null): static;

    /**
     * Constructs and returns a date object given the date's constituent properties
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromParts/
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
    public function dateFromParts($year = null, $isoWeekYear = null, $month = null, $isoWeek = null, $day = null, $isoDayOfWeek = null, $hour = null, $minute = null, $second = null, $millisecond = null, $timezone = null): static;

    /**
     * Converts a date/time string to a date object.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromString/
     *
     * @param mixed|Expr $dateString
     * @param mixed|Expr $format
     * @param mixed|Expr $timezone
     * @param mixed|Expr $onError
     * @param mixed|Expr $onNull
     */
    public function dateFromString($dateString, $format = null, $timezone = null, $onError = null, $onNull = null): static;

    /**
     * Decrements a date object by a specified number of time units
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateSubtract/
     *
     * @param mixed|Expr $startDate
     * @param mixed|Expr $unit
     * @param mixed|Expr $amount
     * @param mixed|Expr $timezone
     */
    public function dateSubtract($startDate, $unit, $amount, $timezone = null): static;

    /**
     * Returns a document that contains the constituent parts of a given BSON
     * date value as individual properties. The properties returned are year,
     * month, day, hour, minute, second and millisecond.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToParts/
     *
     * @param mixed|Expr $date
     * @param mixed|Expr $timezone
     * @param mixed|Expr $iso8601
     */
    public function dateToParts($date, $timezone = null, $iso8601 = null): static;

    /**
     * Converts a date object to a string according to a user-specified format.
     *
     * The format string can be any string literal, containing 0 or more format
     * specifiers.
     * The date argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToString/
     *
     * @param mixed|Expr      $expression
     * @param mixed|Expr|null $timezone
     * @param mixed|Expr|null $onNull
     */
    public function dateToString(string $format, $expression, $timezone = null, $onNull = null): static;

    /**
     * Truncates a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateTrunc/
     *
     * @param mixed|Expr $date
     * @param mixed|Expr $unit
     * @param mixed|Expr $binSize
     * @param mixed|Expr $timezone
     * @param mixed|Expr $startOfWeek
     */
    public function dateTrunc($date, $unit, $binSize = null, $timezone = null, $startOfWeek = null): static;

    /**
     * Returns the day of the month for a date as a number between 1 and 31.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfMonth/
     *
     * @param mixed|Expr $expression
     */
    public function dayOfMonth($expression): static;

    /**
     * Returns the day of the week for a date as a number between 1 (Sunday) and
     * 7 (Saturday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfWeek/
     *
     * @param mixed|Expr $expression
     */
    public function dayOfWeek($expression): static;

    /**
     * Returns the day of the year for a date as a number between 1 and 366.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfYear/
     *
     * @param mixed|Expr $expression
     */
    public function dayOfYear($expression): static;

    /**
     * Returns the hour portion of a date as a number between 0 and 23.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/hour/
     *
     * @param mixed|Expr $expression
     */
    public function hour($expression): static;

    /**
     * Returns the weekday number in ISO 8601 format, ranging from 1 (for
     * Monday) to 7 (for Sunday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoDayOfWeek/
     *
     * @param mixed|Expr $expression
     */
    public function isoDayOfWeek($expression): static;

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
     */
    public function isoWeek($expression): static;

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
     */
    public function isoWeekYear($expression): static;

    /**
     * Returns the millisecond portion of a date as an integer between 0 and 999.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/millisecond/
     *
     * @param mixed|Expr $expression
     */
    public function millisecond($expression): static;

    /**
     * Returns the minute portion of a date as a number between 0 and 59.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minute/
     *
     * @param mixed|Expr $expression
     */
    public function minute($expression): static;

    /**
     * Returns the month of a date as a number between 1 and 12.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/month/
     *
     * @param mixed|Expr $expression
     */
    public function month($expression): static;

    /**
     * Returns the second portion of a date as a number between 0 and 59, but
     * can be 60 to account for leap seconds.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/second/
     *
     * @param mixed|Expr $expression
     */
    public function second($expression): static;

    /**
     * Converts value to a Date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDate/
     *
     * @param mixed|Expr $expression
     */
    public function toDate($expression): static;

    /**
     * Returns the week of the year for a date as a number between 0 and 53.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/week/
     *
     * @param mixed|Expr $expression
     */
    public function week($expression): static;

    /**
     * Returns the year portion of a date.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/year/
     *
     * @param mixed|Expr $expression
     */
    public function year($expression): static;
}
