<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all operators available in most pipeline stages.
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface GenericOperatorsInterface
{
    /**
     * Returns the absolute value of a number.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/abs/
     *
     * @param mixed|Expr $number
     */
    public function abs($number): self;

    /**
     * Returns the inverse cosine (arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acos/
     *
     * @param mixed|Expr $expression
     */
    public function acos($expression): self;

    /**
     * Returns the inverse hyperbolic cosine (hyperbolic arc cosine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/acosh/
     *
     * @param mixed|Expr $expression
     */
    public function acosh($expression): self;

    /**
     * Adds numbers together or adds numbers and a date. If one of the arguments
     * is a date, $add treats the other arguments as milliseconds to add to the
     * date.
     *
     * The arguments can be any valid expression as long as they resolve to
     * either all numbers or to numbers and a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/add/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function add($expression1, $expression2, ...$expressions): self;

    /**
     * Adds one or more $and clauses to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/and/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addAnd($expression, ...$expressions): self;

    /**
     * Adds one or more $or clause to the current expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/or/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addOr($expression, ...$expressions): self;

    /**
     * Evaluates an array as a set and returns true if no element in the array
     * is false. Otherwise, returns false. An empty array returns true.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/allElementsTrue/
     *
     * @param mixed|Expr $expression
     */
    public function allElementsTrue($expression): self;

    /**
     * Evaluates an array as a set and returns true if any of the elements are
     * true and false otherwise. An empty array returns false.
     *
     * The expression must resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/anyElementTrue/
     *
     * @param mixed[]|Expr $expression
     */
    public function anyElementTrue($expression): self;

    /**
     * Returns the element at the specified array index.
     *
     * The <array> expression can be any valid expression as long as it resolves
     * to an array.
     * The <idx> expression can be any valid expression as long as it resolves
     * to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayElemAt/
     *
     * @param mixed|Expr $array
     * @param mixed|Expr $index
     */
    public function arrayElemAt($array, $index): self;

    /**
     * Converts an array into a single document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayToObject/
     *
     * @param mixed|Expr $array
     */
    public function arrayToObject($array): self;

    /**
     * Returns the inverse sin (arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asin/
     *
     * @param mixed|Expr $expression
     */
    public function asin($expression): self;

    /**
     * Returns the inverse hyperbolic sine (hyperbolic arc sine) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/asinh/
     *
     * @param mixed|Expr $expression
     */
    public function asinh($expression): self;

    /**
     * Returns the inverse tangent (arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan/
     *
     * @param mixed|Expr $expression
     */
    public function atan($expression): self;

    /**
     * Returns the inverse tangent (arc tangent) of y / x in radians, where y and x are the first and second values passed to the expression respectively.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atan2/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function atan2($expression1, $expression2): self;

    /**
     * Returns the inverse hyperbolic tangent (hyperbolic arc tangent) of a value in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/atanh/
     *
     * @param mixed|Expr $expression
     */
    public function atanh($expression): self;

    /**
     * Adds a case statement for a branch of the $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression that resolves to a boolean. If the result is not a
     * boolean, it is coerced to a boolean value.
     *
     * @param mixed|Expr $expression
     */
    public function case($expression): self;

    /**
     * Returns the smallest integer greater than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ceil/
     *
     * @param mixed|Expr $number
     */
    public function ceil($number): self;

    /**
     * Compares two values and returns:
     * -1 if the first value is less than the second.
     * 1 if the first value is greater than the second.
     * 0 if the two values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cmp/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function cmp($expression1, $expression2): self;

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
    public function concat($expression1, $expression2, ...$expressions): self;

    /**
     * Concatenates arrays to return the concatenated array.
     *
     * The <array> expressions can be any valid expression as long as they
     * resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/concatArrays/
     *
     * @param mixed|Expr $array1
     * @param mixed|Expr $array2
     * @param mixed|Expr ...$arrays Additional expressions
     */
    public function concatArrays($array1, $array2, ...$arrays): self;

    /**
     * Evaluates a boolean expression to return one of the two specified return
     * expressions.
     *
     * The arguments can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cond/
     *
     * @param mixed|Expr $if
     * @param mixed|Expr $then
     * @param mixed|Expr $else
     */
    public function cond($if, $then, $else): self;

    /**
     * Converts a value to a specified type.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/convert/
     *
     * @param mixed|Expr      $input
     * @param mixed|Expr      $to
     * @param mixed|Expr|null $onError
     * @param mixed|Expr|null $onNull
     */
    public function convert($input, $to, $onError = null, $onNull = null): self;

    /**
     * Returns the cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cos/
     *
     * @param mixed|Expr $expression
     */
    public function cos($expression): self;

    /**
     * Returns the hyperbolic cosine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/cosh/
     *
     * @param mixed|Expr $expression
     */
    public function cosh($expression): self;

    /**
     * Converts a date object to a string according to a user-specified format.
     *
     * The format string can be any string literal, containing 0 or more format
     * specifiers.
     * The date argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dateToString/
     *
     * @param mixed|Expr $expression
     */
    public function dateToString(string $format, $expression): self;

    /**
     * Returns the day of the month for a date as a number between 1 and 31.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfMonth/
     *
     * @param mixed|Expr $expression
     */
    public function dayOfMonth($expression): self;

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
    public function dayOfWeek($expression): self;

    /**
     * Returns the day of the year for a date as a number between 1 and 366.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/dayOfYear/
     *
     * @param mixed|Expr $expression
     */
    public function dayOfYear($expression): self;

    /**
     * Adds a default statement for the current $switch operator.
     *
     * Requires {@link switch()} to be called first. The argument can be any
     * valid expression.
     *
     * Note: if no default is specified and no branch evaluates to true, the
     * $switch operator throws an error.
     *
     * @param mixed|Expr $expression
     */
    public function default($expression): self;

    /**
     * Converts a value from degrees to radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/degreesToRadians/
     *
     * @param mixed|Expr $expression
     */
    public function degreesToRadians($expression): self;

    /**
     * Divides one number by another and returns the result. The first argument
     * is divided by the second argument.
     *
     * The arguments can be any valid expression as long as the resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/divide/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function divide($expression1, $expression2): self;

    /**
     * Compares two values and returns whether the are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/eq/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function eq($expression1, $expression2): self;

    /**
     * Raises Euler’s number to the specified exponent and returns the result.
     *
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/exp/
     *
     * @param mixed|Expr $exponent
     */
    public function exp($exponent): self;

    /**
     * Allows any expression to be used as a field value.
     *
     * @see https://docs.mongodb.com/manual/meta/aggregation-quick-reference/#aggregation-expressions
     *
     * @param mixed|Expr $value
     *
     * @return self
     */
    public function expression($value);

    /**
     * Set the current field for building the expression.
     *
     * @return self
     */
    public function field(string $fieldName);

    /**
     * Selects a subset of the array to return based on the specified condition.
     *
     * Returns an array with only those elements that match the condition. The
     * returned elements are in the original order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/filter/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $as
     * @param mixed|Expr $cond
     */
    public function filter($input, $as, $cond): self;

    /**
     * Returns the value that results from applying an expression to the first
     * document in a group of documents that share the same group by key. Only
     * meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/first/
     *
     * @param mixed|Expr $expression
     */
    public function first($expression): self;

    /**
     * Returns the largest integer less than or equal to the specified number.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/floor/
     *
     * @param mixed|Expr $number
     */
    public function floor($number): self;

    /**
     * Compares two values and returns:
     * true when the first value is greater than the second value.
     * false when the first value is less than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gt/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function gt($expression1, $expression2): self;

    /**
     * Compares two values and returns:
     * true when the first value is greater than or equivalent to the second
     * value.
     * false when the first value is less than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/gte/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function gte($expression1, $expression2): self;

    /**
     * Returns the hour portion of a date as a number between 0 and 23.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/hour/
     *
     * @param mixed|Expr $expression
     */
    public function hour($expression): self;

    /**
     * Evaluates an expression and returns the value of the expression if the
     * expression evaluates to a non-null value. If the expression evaluates to
     * a null value, including instances of undefined values or missing fields,
     * returns the value of the replacement expression.
     *
     * The arguments can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ifNull/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $replacementExpression
     */
    public function ifNull($expression, $replacementExpression): self;

    /**
     * Returns a boolean indicating whether a specified value is in an array.
     *
     * Unlike the $in query operator, the aggregation $in operator does not
     * support matching by regular expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/in/
     *
     * @param mixed|Expr $expression
     * @param mixed|Expr $arrayExpression
     */
    public function in($expression, $arrayExpression): self;

    /**
     * Searches an array for an occurrence of a specified value and returns the
     * array index (zero-based) of the first occurrence. If the value is not
     * found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfArray/
     *
     * @param mixed|Expr $arrayExpression  can be any valid expression as long as it resolves to an array
     * @param mixed|Expr $searchExpression can be any valid expression
     * @param mixed|Expr $start            Optional. An integer, or a number that can be represented as integers (such as 2.0), that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param mixed|Expr $end              An integer, or a number that can be represented as integers (such as 2.0), that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfArray($arrayExpression, $searchExpression, $start = null, $end = null): self;

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
    public function indexOfBytes($stringExpression, $substringExpression, $start = null, $end = null): self;

    /**
     * Searches a string for an occurrence of a substring and returns the UTF-8
     * code point index (zero-based) of the first occurrence. If the substring is
     * not found, returns -1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/indexOfCP/
     *
     * @param mixed|Expr      $stringExpression    can be any valid expression as long as it resolves to a string
     * @param mixed|Expr      $substringExpression can be any valid expression as long as it resolves to a string
     * @param string|int|null $start               An integral number that specifies the starting index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     * @param string|int|null $end                 An integral number that specifies the ending index position for the search. Can be any valid expression that resolves to a non-negative integral number.
     */
    public function indexOfCP($stringExpression, $substringExpression, $start = null, $end = null): self;

    /**
     * Determines if the operand is an array. Returns a boolean.
     *
     * The <expression> can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isArray/
     *
     * @param mixed|Expr $expression
     */
    public function isArray($expression): self;

    /**
     * Returns boolean true if the specified expression resolves to an integer, decimal, double, or long.
     * Returns boolean false if the expression resolves to any other BSON type, null, or a missing field.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isNumber/
     *
     * @param mixed|Expr $expression
     */
    public function isNumber($expression): self;

    /**
     * Returns the weekday number in ISO 8601 format, ranging from 1 (for Monday)
     * to 7 (for Sunday).
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isoDayOfWeek/
     *
     * @param mixed|Expr $expression
     */
    public function isoDayOfWeek($expression): self;

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
    public function isoWeek($expression): self;

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
    public function isoWeekYear($expression): self;

    /**
     * Returns the value that results from applying an expression to the last
     * document in a group of documents that share the same group by a field.
     * Only meaningful when documents are in a defined order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last/
     *
     * @param mixed|Expr $expression
     */
    public function last($expression): self;

    /**
     * Binds variables for use in the specified expression, and returns the
     * result of the expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/let/
     *
     * @param mixed|Expr $vars Assignment block for the variables accessible in the in expression. To assign a variable, specify a string for the variable name and assign a valid expression for the value.
     * @param mixed|Expr $in   the expression to evaluate
     */
    public function let($vars, $in): self;

    /**
     * Returns a value without parsing. Use for values that the aggregation
     * pipeline may interpret as an expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/literal/
     *
     * @param mixed|Expr $value
     */
    public function literal($value): self;

    /**
     * Calculates the natural logarithm ln (i.e loge) of a number and returns
     * the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     *
     * @param mixed|Expr $number
     */
    public function ln($number): self;

    /**
     * Calculates the log of a number in the specified base and returns the
     * result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <base> expression can be any valid expression as long as it resolves
     * to a positive number greater than 1.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log/
     *
     * @param mixed|Expr $number
     * @param mixed|Expr $base
     */
    public function log($number, $base): self;

    /**
     * Calculates the log base 10 of a number and returns the result as a double.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/log10/
     *
     * @param mixed|Expr $number
     */
    public function log10($number): self;

    /**
     * Compares two values and returns:
     * true when the first value is less than the second value.
     * false when the first value is greater than or equivalent to the second
     * value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lt/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function lt($expression1, $expression2): self;

    /**
     * Compares two values and returns:
     * true when the first value is less than or equivalent to the second value.
     * false when the first value is greater than the second value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lte/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function lte($expression1, $expression2): self;

    /**
     * Removes whitespace characters, including null, or the specified characters from
     * the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ltrim/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $chars
     */
    public function ltrim($input, $chars = null): self;

    /**
     * Applies an expression to each item in an array and returns an array with
     * the applied results.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/map/
     *
     * @param mixed|Expr $input an expression that resolves to an array
     * @param string     $as    The variable name for the items in the input array. The in expression accesses each item in the input array by this variable.
     * @param mixed|Expr $in    The expression to apply to each item in the input array. The expression accesses the item by its variable name.
     */
    public function map($input, $as, $in): self;

    /**
     * Returns the metadata associated with a document in a pipeline operations.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/meta/
     *
     * @param mixed|Expr $metaDataKeyword
     */
    public function meta($metaDataKeyword): self;

    /**
     * Returns the millisecond portion of a date as an integer between 0 and 999.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/millisecond/
     *
     * @param mixed|Expr $expression
     */
    public function millisecond($expression): self;

    /**
     * Returns the minute portion of a date as a number between 0 and 59.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minute/
     *
     * @param mixed|Expr $expression
     */
    public function minute($expression): self;

    /**
     * Divides one number by another and returns the remainder. The first
     * argument is divided by the second argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/mod/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function mod($expression1, $expression2): self;

    /**
     * Returns the month of a date as a number between 1 and 12.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/month/
     *
     * @param mixed|Expr $expression
     */
    public function month($expression): self;

    /**
     * Multiplies numbers together and returns the result.
     *
     * The arguments can be any valid expression as long as they resolve to numbers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/multiply/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional expressions
     */
    public function multiply($expression1, $expression2, ...$expressions): self;

    /**
     * Compares two values and returns:
     * true when the values are not equivalent.
     * false when the values are equivalent.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/ne/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function ne($expression1, $expression2): self;

    /**
     * Evaluates a boolean and returns the opposite boolean value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/not/
     *
     * @param mixed|Expr $expression
     */
    public function not($expression): self;

    /**
     * Converts a document to an array. The return array contains an element for each field/value pair
     * in the original document. Each element in the return array is a document that contains
     * two fields k and v:.
     *      The k field contains the field name in the original document.
     *      The v field contains the value of the field in the original document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/objectToArray/
     *
     * @param mixed|Expr $object
     */
    public function objectToArray($object): self;

    /**
     * Raises a number to the specified exponent and returns the result.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a non-negative number.
     * The <exponent> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/pow/
     *
     * @param mixed|Expr $number
     * @param mixed|Expr $exponent
     */
    public function pow($number, $exponent): self;

    /**
     * Converts a value from radians to degrees.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/radiansToDegrees/
     *
     * @param mixed|Expr $expression
     */
    public function radiansToDegrees($expression): self;

    /**
     * Returns an array whose elements are a generated sequence of numbers.
     *
     * $range generates the sequence from the specified starting number by successively incrementing the starting number by the specified step value up to but not including the end point.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/range/
     *
     * @param mixed|Expr $start An integer that specifies the start of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|Expr $end   An integer that specifies the exclusive upper limit of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|Expr $step  Optional. An integer that specifies the increment value. Can be any valid expression that resolves to a non-zero integer. Defaults to 1.
     */
    public function range($start, $end, $step = 1): self;

    /**
     * Applies an expression to each element in an array and combines them into
     * a single value.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reduce/
     *
     * @param mixed|Expr $input        can be any valid expression that resolves to an array
     * @param mixed|Expr $initialValue the initial cumulative value set before in is applied to the first element of the input array
     * @param mixed|Expr $in           A valid expression that $reduce applies to each element in the input array in left-to-right order. Wrap the input value with $reverseArray to yield the equivalent of applying the combining expression from right-to-left.
     */
    public function reduce($input, $initialValue, $in): self;

    /**
     * Accepts an array expression as an argument and returns an array with the
     * elements in reverse order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reverseArray/
     *
     * @param mixed|Expr $expression
     */
    public function reverseArray($expression): self;

    /**
     * Rounds a number to a whole integer or to a specified decimal place.
     *
     * The <number> argument can be any valid expression as long as it resolves
     * to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/round/
     *
     * @param mixed|Expr      $number
     * @param mixed|Expr|null $place
     */
    public function round($number, $place = null): self;

    /**
     * Removes whitespace characters, including null, or the specified characters from the end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/rtrim/
     *
     * @param mixed|Expr $input
     * @param mixed|Expr $chars
     */
    public function rtrim($input, $chars = null): self;

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
    public function second($expression): self;

    /**
     * Takes two sets and returns an array containing the elements that only
     * exist in the first set.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setDifference/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function setDifference($expression1, $expression2): self;

    /**
     * Compares two or more arrays and returns true if they have the same
     * distinct elements and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setEquals/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setEquals($expression1, $expression2, ...$expressions): self;

    /**
     * Takes two or more arrays and returns an array that contains the elements
     * that appear in every input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIntersection/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setIntersection($expression1, $expression2, ...$expressions): self;

    /**
     * Takes two arrays and returns true when the first array is a subset of the
     * second, including when the first array equals the second array, and false otherwise.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setIsSubset/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function setIsSubset($expression1, $expression2): self;

    /**
     * Takes two or more arrays and returns an array containing the elements
     * that appear in any input array.
     *
     * The arguments can be any valid expression as long as they each resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/setUnion/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     * @param mixed|Expr ...$expressions Additional sets
     */
    public function setUnion($expression1, $expression2, ...$expressions): self;

    /**
     * Returns the sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sin/
     *
     * @param mixed|Expr $expression
     */
    public function sin($expression): self;

    /**
     * Returns the hyperbolic sine of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sinh/
     *
     * @param mixed|Expr $expression
     */
    public function sinh($expression): self;

    /**
     * Counts and returns the total the number of items in an array.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/size/
     *
     * @param mixed|Expr $expression
     */
    public function size($expression): self;

    /**
     * Returns a subset of an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/slice/
     *
     * @param mixed|Expr      $array
     * @param mixed|Expr      $n
     * @param mixed|Expr|null $position
     */
    public function slice($array, $n, $position = null): self;

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
    public function split($string, $delimiter): self;

    /**
     * Calculates the square root of a positive number and returns the result as
     * a double.
     *
     * The argument can be any valid expression as long as it resolves to a
     * non-negative number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sqrt/
     *
     * @param mixed|Expr $expression
     */
    public function sqrt($expression): self;

    /**
     * Returns the number of UTF-8 encoded bytes in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenBytes/
     *
     * @param mixed|Expr $string
     */
    public function strLenBytes($string): self;

    /**
     * Returns the number of UTF-8 code points in the specified string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strLenCP/
     *
     * @param mixed|Expr $string
     */
    public function strLenCP($string): self;

    /**
     * Performs case-insensitive comparison of two strings. Returns
     * 1 if first string is “greater than” the second string.
     * 0 if the two strings are equal.
     * -1 if the first string is “less than” the second string.
     *
     * The arguments can be any valid expression as long as they resolve to strings.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/strcasecmp/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function strcasecmp($expression1, $expression2): self;

    /**
     * Returns a substring of a string, starting at a specified index position
     * and including the specified number of characters. The index is zero-based.
     *
     * The arguments can be any valid expression as long as long as the first argument resolves to a string, and the second and third arguments resolve to integers.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/substr/
     *
     * @param mixed|Expr $string
     * @param mixed|Expr $start
     * @param mixed|Expr $length
     */
    public function substr($string, $start, $length): self;

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
    public function substrBytes($string, $start, $count): self;

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
    public function substrCP($string, $start, $count): self;

    /**
     * Subtracts two numbers to return the difference. The second argument is
     * subtracted from the first argument.
     *
     * The arguments can be any valid expression as long as they resolve to numbers and/or dates.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/subtract/
     *
     * @param mixed|Expr $expression1
     * @param mixed|Expr $expression2
     */
    public function subtract($expression1, $expression2): self;

    /**
     * Evaluates a series of case expressions. When it finds an expression which
     * evaluates to true, $switch executes a specified expression and breaks out
     * of the control flow.
     *
     * To add statements, use the {@link case()}, {@link then()} and
     * {@link default()} methods.
     */
    public function switch(): self;

    /**
     * Returns the tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tan/
     *
     * @param mixed|Expr $expression
     */
    public function tan($expression): self;

    /**
     * Returns the hyperbolic tangent of a value that is measured in radians.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/tanh/
     *
     * @param mixed|Expr $expression
     */
    public function tanh($expression): self;

    /**
     * Adds a case statement for the current branch of the $switch operator.
     *
     * Requires {@link case()} to be called first. The argument can be any valid
     * expression.
     *
     * @param mixed|Expr $expression
     */
    public function then($expression): self;

    /**
     * Converts value to a boolean.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toBool/
     *
     * @param mixed|Expr $expression
     */
    public function toBool($expression): self;

    /**
     * Converts value to a Date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDate/
     *
     * @param mixed|Expr $expression
     */
    public function toDate($expression): self;

    /**
     * Converts value to a Decimal128.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDecimal/
     *
     * @param mixed|Expr $expression
     */
    public function toDecimal($expression): self;

    /**
     * Converts value to a double.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toDouble/
     *
     * @param mixed|Expr $expression
     */
    public function toDouble($expression): self;

    /**
     * Converts value to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toInt/
     *
     * @param mixed|Expr $expression
     */
    public function toInt($expression): self;

    /**
     * Converts value to a long.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLong/
     *
     * @param mixed|Expr $expression
     */
    public function toLong($expression): self;

    /**
     * Converts a string to lowercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toLower/
     *
     * @param mixed|Expr $expression
     */
    public function toLower($expression): self;

    /**
     * Converts value to an ObjectId.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toObjectId/
     *
     * @param mixed|Expr $expression
     */
    public function toObjectId($expression): self;

    /**
     * Converts value to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toString/
     *
     * @param mixed|Expr $expression
     */
    public function toString($expression): self;

    /**
     * Converts a string to uppercase, returning the result.
     *
     * The argument can be any expression as long as it resolves to a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/toUpper/
     *
     * @param mixed|Expr $expression
     */
    public function toUpper($expression): self;

    /**
     * Removes whitespace characters, including null, or the specified characters from
     * the beginning and end of a string.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trim/
     *
     * @param mixed|Expr      $input
     * @param mixed|Expr|null $chars
     */
    public function trim($input, $chars = null): self;

    /**
     * Truncates a number to its integer.
     *
     * The <number> expression can be any valid expression as long as it
     * resolves to a number.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/trunc/
     *
     * @param mixed|Expr $number
     */
    public function trunc($number): self;

    /**
     * Returns a string that specifies the BSON type of the argument.
     *
     * The argument can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/type/
     *
     * @param mixed|Expr $expression
     */
    public function type($expression): self;

    /**
     * Returns the week of the year for a date as a number between 0 and 53.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/week/
     *
     * @param mixed|Expr $expression
     */
    public function week($expression): self;

    /**
     * Returns the year portion of a date.
     *
     * The argument can be any expression as long as it resolves to a date.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/year/
     *
     * @param mixed|Expr $expression
     */
    public function year($expression): self;

    /**
     * Transposes an array of input arrays so that the first element of the
     * output array would be an array containing, the first element of the first
     * input array, the first element of the second input array, etc.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/zip/
     *
     * @param mixed|Expr      $inputs           An array of expressions that resolve to arrays. The elements of these input arrays combine to form the arrays of the output array.
     * @param bool|null       $useLongestLength a boolean which specifies whether the length of the longest array determines the number of arrays in the output array
     * @param mixed|Expr|null $defaults         An array of default element values to use if the input arrays have different lengths. You must specify useLongestLength: true along with this field, or else $zip will return an error.
     */
    public function zip($inputs, ?bool $useLongestLength = null, $defaults = null): self;
}
