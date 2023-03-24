<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all string aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface StringOperators
{
    /**
     * Concatenates strings and returns the concatenated string.
     *
     * The arguments can be any valid expression as long as they resolve to
     * strings. If the argument resolves to a value of null or refers to a field
     * that is missing, $concat returns null.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/concat/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function concat($expression1, $expression2, ...$expressions): static;

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
     * Searches a string for an occurrence of a substring and returns the UTF-8
     * byte index (zero-based) of the first occurrence. If the substring is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfBytes/
     *
     * @param mixed|Expr      $stringExpression    can be any valid expression as long as it resolves to a string
     * @param mixed|Expr      $substringExpression can be any valid expression as long as it resolves to a string
     * @param string|int|null $start               An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param string|int|null $end                 An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): static;

    /**
     * Searches a string for an occurrence of a substring and returns the UTF-8
     * code point index (zero-based) of the first occurrence. If the substring
     * is not found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfCP/
     *
     * @param mixed|Expr      $stringExpression    can be any valid expression as long as it resolves to a string
     * @param mixed|Expr      $substringExpression can be any valid expression as long as it resolves to a string
     * @param string|int|null $start               An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param string|int|null $end                 An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): static;

    /**
     * Removes whitespace characters, including null, or the specified
     * characters from the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ltrim/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $chars
     */
    public function ltrim($input, $chars = null): static;

    /**
     * Provides regular expression (regex) pattern matching capability in
     * aggregation expressions.
     *
     * If a match is found, returns a document that contains information on the
     * first match. If a match is not found, returns null.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/regexFind/
     *
     * @param mixed|Expr  $input
     * @param mixed|Expr  $regex
     * @param string|null $options
     */
    public function regexFind($input, $regex, $options = null): static;

    /**
     * Provides regular expression (regex) pattern matching capability in
     * aggregation expressions.
     *
     * The operator returns an array of documents that contains information on
     * each match. If a match is not found, returns an empty array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/regexFindAll/
     *
     * @param mixed|Expr  $input
     * @param mixed|Expr  $regex
     * @param string|null $options
     */
    public function regexFindAll($input, $regex, $options = null): static;

    /**
     * Performs a regular expression (regex) pattern matching and returns true
     * if a match exists.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/regexMatch/
     *
     * @param mixed|Expr  $input
     * @param mixed|Expr  $regex
     * @param string|null $options
     */
    public function regexMatch($input, $regex, $options = null): static;

    /**
     * Replaces all instances of a search string in an input string with a
     * replacement string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/replaceAll/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $find
     * @param mixed|Expr $replacement
     */
    public function replaceAll($input, $find, $replacement): static;

    /**
     * Replaces the first instance of a search string in an input string with a
     * replacement string. If no occurrences are found, it evaluates to the
     * input string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/replaceOne/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $find
     * @param mixed|Expr $replacement
     */
    public function replaceOne($input, $find, $replacement): static;

    /**
     * Removes whitespace characters, including null, or the specified
     * characters from the end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rtrim/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $chars
     */
    public function rtrim($input, $chars = null): static;

    /**
     * Divides a string into an array of substrings based on a delimiter.
     *
     * $split removes the delimiter and returns the resulting substrings as
     * elements of an array. If the delimiter is not found in the string, $split
     * returns the original string as the only element of an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/split/
     *
     * @param mixed|Expr $string    The string to be split. Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr $delimiter The delimiter to use when splitting the string expression. Can be any valid expression as long as it resolves to a string.
     */
    public function split($string, $delimiter): static;

    /**
     * Returns the number of UTF-8 encoded bytes in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenBytes/
     *
     * @param mixed|Expr $string
     */
    public function strLenBytes($string): static;

    /**
     * Returns the number of UTF-8 code points in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenCP/
     *
     * @param mixed|Expr $string
     */
    public function strLenCP($string): static;

    /**
     * Performs case-insensitive comparison of two strings. Returns
     * 1 if first string is “greater than” the second string.
     * 0 if the two strings are equal.
     * -1 if the first string is “less than” the second string.
     *
     * The arguments can be any valid expression as long as they resolve to
     * strings.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strcasecmp/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function strcasecmp($expression1, $expression2): static;

    /**
     * Returns a substring of a string, starting at a specified index position
     * and including the specified number of characters. The index is zero-based.
     *
     * The arguments can be any valid expression as long as long as the first
     * argument resolves to a string, and the second and third arguments resolve
     * to integers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substr/
     *
     * @param mixed|Expr $string
     * @param mixed|Expr $start
     * @param mixed|Expr $length
     */
    public function substr($string, $start, $length): static;

    /**
     * Returns the substring of a string.
     *
     * The substring starts with the character at the specified UTF-8 byte index
     * (zero-based) in the string and continues for the number of bytes
     * specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substrBytes/
     *
     * @param mixed|Expr $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr $start  Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|Expr $count  can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer
     */
    public function substrBytes($string, $start, $count): static;

    /**
     * Returns the substring of a string.
     *
     * The substring starts with the character at the specified UTF-8 code point
     * (CP) index (zero-based) in the string for the number of code points
     * specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substrBytes/
     *
     * @param mixed|Expr $string The string from which the substring will be extracted. Can be any valid expression as long as it resolves to a string.
     * @param mixed|Expr $start  Indicates the starting point of the substring. Can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer.
     * @param mixed|Expr $count  can be any valid expression as long as it resolves to a non-negative integer or number that can be represented as an integer
     */
    public function substrCP($string, $start, $count): static;

    /**
     * Converts a string to lowercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLower/
     *
     * @param mixed|Expr $expression
     */
    public function toLower($expression): static;

    /**
     * Converts value to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toString/
     *
     * @param mixed|Expr $expression
     */
    public function toString($expression): static;

    /**
     * Converts a string to uppercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toUpper/
     *
     * @param mixed|Expr $expression
     */
    public function toUpper($expression): static;

    /**
     * Removes whitespace characters, including null, or the specified
     * characters from the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trim/
     *
     * @param mixed|Expr      $input
     * @param mixed|Expr|null $chars
     */
    public function trim($input, $chars = null): static;
}
