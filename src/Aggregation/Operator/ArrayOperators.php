<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Operator;

use Doctrine\ODM\MongoDB\Aggregation\Expr;

/**
 * Interface containing all array aggregation pipeline operators.
 *
 * This interface can be used for type hinting, but must not be implemented by
 * users. Methods WILL be added to the public API in future minor versions.
 *
 * @internal
 */
interface ArrayOperators
{
    /**
     * Returns the element at the specified array index.
     *
     * The <array> expression can be any valid expression as long as it resolves
     * to an array.
     * The <idx> expression can be any valid expression as long as it resolves
     * to an integer.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayElemAt/
     */
    public function arrayElemAt(mixed $array, mixed $index): static;

    /**
     * Converts an array into a single document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/arrayToObject/
     */
    public function arrayToObject(mixed $array): static;

    /**
     * Concatenates arrays to return the concatenated array.
     *
     * The <array> expressions can be any valid expression as long as they
     * resolve to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/concatArrays/
     *
     * @param mixed|Expr ...$arrays Additional expressions
     */
    public function concatArrays(mixed $array1, mixed $array2, mixed ...$arrays): static;

    /**
     * Selects a subset of the array to return based on the specified condition.
     *
     * Returns an array with only those elements that match the condition. The
     * returned elements are in the original order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/filter/
     */
    public function filter(mixed $input, mixed $as, mixed $cond): static;

    /**
     * Returns the first array element. Distinct from the $first group
     * accumulator.
     *
     * @see https://www.mongodb.com/docs/manual/reference/operator/aggregation/first-array-element/
     */
    public function first(mixed $expression): static;

    /**
     * Returns a specified number of elements from the beginning of an array.
     * Distinct from the $firstN group accumulator.
     *
     * @see https://www.mongodb.com/docs/manual/reference/operator/aggregation/firstN-array-element/
     */
    public function firstN(mixed $expression, mixed $n): static;

    /**
     * Returns a boolean indicating whether a specified value is in an array.
     *
     * Unlike the $in query operator, the aggregation $in operator does not
     * support matching by regular expressions.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/in/
     */
    public function in(mixed $expression, mixed $arrayExpression): static;

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
    public function indexOfArray(mixed $arrayExpression, mixed $searchExpression, mixed $start = null, mixed $end = null): static;

    /**
     * Determines if the operand is an array. Returns a boolean.
     *
     * The <expression> can be any valid expression.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/isArray/
     */
    public function isArray(mixed $expression): static;

    /**
     * Returns the last array element. Distinct from the $last group accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/last-array-element/
     */
    public function last(mixed $expression): static;

    /**
     * Returns a specified number of elements from the end of an array. Distinct
     * from the $lastN accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/lastN-array-element/
     */
    public function lastN(mixed $expression, mixed $n): static;

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
    public function map(mixed $input, string $as, mixed $in): static;

    /**
     * Returns the n largest values in an array. Distinct from the $maxN group
     * accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/maxN/
     */
    public function maxN(mixed $expression, mixed $n): static;

    /**
     * Returns the n smallest values in an array. Distinct from the $minN group
     * accumulator.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/minN/
     */
    public function minN(mixed $expression, mixed $n): static;

    /**
     * Converts a document to an array. The return array contains an element for
     * each field/value pair in the original document. Each element in the
     * return array is a document that contains two fields k and v:.
     *      The k field contains the field name in the original document.
     *      The v field contains the value of the field in the original document.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/objectToArray/
     */
    public function objectToArray(mixed $object): static;

    /**
     * Returns an array whose elements are a generated sequence of numbers.
     *
     * $range generates the sequence from the specified starting number by
     * successively incrementing the starting number by the specified step value
     * up to but not including the end point.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/range/
     *
     * @param mixed|Expr $start An integer that specifies the start of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|Expr $end   An integer that specifies the exclusive upper limit of the sequence. Can be any valid expression that resolves to an integer.
     * @param mixed|Expr $step  Optional. An integer that specifies the increment value. Can be any valid expression that resolves to a non-zero integer. Defaults to 1.
     */
    public function range(mixed $start, mixed $end, mixed $step = null): static;

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
    public function reduce(mixed $input, mixed $initialValue, mixed $in): static;

    /**
     * Accepts an array expression as an argument and returns an array with the
     * elements in reverse order.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/reverseArray/
     */
    public function reverseArray(mixed $expression): static;

    /**
     * Counts and returns the total the number of items in an array.
     *
     * The argument can be any expression as long as it resolves to an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/size/
     */
    public function size(mixed $expression): static;

    /**
     * Returns a subset of an array.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/slice/
     */
    public function slice(mixed $array, mixed $n, mixed $position = null): static;

    /**
     * Sorts an array based on its elements. The sort order is user specified.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/aggregation/sortArray/
     *
     * @param array<string, int|string> $sortBy
     */
    public function sortArray(mixed $input, array $sortBy): static;

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
    public function zip(mixed $inputs, ?bool $useLongestLength = null, mixed $defaults = null): static;
}
