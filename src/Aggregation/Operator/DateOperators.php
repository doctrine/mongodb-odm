<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

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
     */
    public function dateAdd(mixed $startDate, mixed $unit, mixed $amount, mixed $timezone = null): static;

    /**
     * Returns the difference between two dates
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateDiff/
     */
    public function dateDiff(mixed $startDate, mixed $endDate, mixed $unit, mixed $timezone = null, mixed $startOfWeek = null): static;

    /**
     * Constructs and returns a date object given the date's constituent properties
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromParts/
     */
    public function dateFromParts(mixed $year = null, mixed $isoWeekYear = null, mixed $month = null, mixed $isoWeek = null, mixed $day = null, mixed $isoDayOfWeek = null, mixed $hour = null, mixed $minute = null, mixed $second = null, mixed $millisecond = null, mixed $timezone = null): static;

    /**
     * Converts a date/time string to a date object.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateFromString/
     */
    public function dateFromString(mixed $dateString, mixed $format = null, mixed $timezone = null, mixed $onError = null, mixed $onNull = null): static;

    /**
     * Decrements a date object by a specified number of time units
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateSubtract/
     */
    public function dateSubtract(mixed $startDate, mixed $unit, mixed $amount, mixed $timezone = null): static;

    /**
     * Returns a document that contains the constituent parts of a given BSON
     * date value as individual properties. The properties returned are year,
     * month, day, hour, minute, second and millisecond.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToParts/
     */
    public function dateToParts(mixed $date, mixed $timezone = null, mixed $iso8601 = null): static;

    /**
     * Converts a date object to a string according to a user-specified format.
     *
     * The format string can be any string literal, containing 0 or more format
     * specifiers.
     * The date argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToString/
     */
    public function dateToString(string $format, mixed $expression, mixed $timezone = null, mixed $onNull = null): static;

    /**
     * Truncates a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateTrunc/
     */
    public function dateTrunc(mixed $date, mixed $unit, mixed $binSize = null, mixed $timezone = null, mixed $startOfWeek = null): static;

    /**
     * Returns the day of the month for a date as a number between 1 and 31.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfMonth/
     */
    public function dayOfMonth(mixed $expression): static;

    /**
     * Returns the day of the week for a date as a number between 1 (Sunday) and
     * 7 (Saturday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfWeek/
     */
    public function dayOfWeek(mixed $expression): static;

    /**
     * Returns the day of the year for a date as a number between 1 and 366.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfYear/
     */
    public function dayOfYear(mixed $expression): static;

    /**
     * Returns the hour portion of a date as a number between 0 and 23.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/hour/
     */
    public function hour(mixed $expression): static;

    /**
     * Returns the weekday number in ISO 8601 format, ranging from 1 (for
     * Monday) to 7 (for Sunday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoDayOfWeek/
     */
    public function isoDayOfWeek(mixed $expression): static;

    /**
     * Returns the week number in ISO 8601 format, ranging from 1 to 53.
     *
     * Week numbers start at 1 with the week (Monday through Sunday) that
     * contains the year’s first Thursday.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoWeek/
     */
    public function isoWeek(mixed $expression): static;

    /**
     * Returns the year number in ISO 8601 format.
     *
     * The year starts with the Monday of week 1 (ISO 8601) and ends with the
     * Sunday of the last week (ISO 8601).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoWeek/
     */
    public function isoWeekYear(mixed $expression): static;

    /**
     * Returns the millisecond portion of a date as an integer between 0 and 999.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/millisecond/
     */
    public function millisecond(mixed $expression): static;

    /**
     * Returns the minute portion of a date as a number between 0 and 59.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minute/
     */
    public function minute(mixed $expression): static;

    /**
     * Returns the month of a date as a number between 1 and 12.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/month/
     */
    public function month(mixed $expression): static;

    /**
     * Returns the second portion of a date as a number between 0 and 59, but
     * can be 60 to account for leap seconds.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/second/
     */
    public function second(mixed $expression): static;

    /**
     * Converts value to a Date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDate/
     */
    public function toDate(mixed $expression): static;

    /**
     * Returns the week of the year for a date as a number between 0 and 53.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/week/
     */
    public function week(mixed $expression): static;

    /**
     * Returns the year portion of a date.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/year/
     */
    public function year(mixed $expression): static;
}
