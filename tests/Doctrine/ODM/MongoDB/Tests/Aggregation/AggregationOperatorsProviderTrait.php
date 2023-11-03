<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Documents\User;
use Generator;
use InvalidArgumentException;

use function is_array;

trait AggregationOperatorsProviderTrait
{
    public static function provideAccumulatorExpressionOperators(): Generator
    {
        yield 'avg' => [
            'expected' => ['$avg' => ['$field1', '$field2']],
            'operator' => 'avg',
            'args' => ['$field1', '$field2'],
        ];

        yield 'max' => [
            'expected' => ['$max' => ['$field1', '$field2']],
            'operator' => 'max',
            'args' => ['$field1', '$field2'],
        ];

        yield 'min' => [
            'expected' => ['$min' => ['$field1', '$field2']],
            'operator' => 'min',
            'args' => ['$field1', '$field2'],
        ];

        yield 'stdDevPop' => [
            'expected' => ['$stdDevPop' => ['$field1', '$field2']],
            'operator' => 'stdDevPop',
            'args' => ['$field1', '$field2'],
        ];

        yield 'stdDevSamp' => [
            'expected' => ['$stdDevSamp' => ['$field1', '$field2']],
            'operator' => 'stdDevSamp',
            'args' => ['$field1', '$field2'],
        ];

        yield 'sum' => [
            'expected' => ['$sum' => ['$field1', '$field2']],
            'operator' => 'sum',
            'args' => ['$field1', '$field2'],
        ];
    }

    public static function provideArithmeticExpressionOperators(): Generator
    {
            yield 'abs' => [
                'expected' => ['$abs' => '$field'],
                'operator' => 'abs',
                'args' => ['$field'],
            ];

            yield 'addWithTwoArgs' => [
                'expected' => ['$add' => [5, '$field']],
                'operator' => 'add',
                'args' => [5, '$field'],
            ];

            yield 'addWithMultipleArgs' => [
                'expected' => ['$add' => [5, '$field', '$otherField', 4.99]],
                'operator' => 'add',
                'args' => [5, '$field', '$otherField', 4.99],
            ];

            yield 'ceil' => [
                'expected' => ['$ceil' => '$field'],
                'operator' => 'ceil',
                'args' => ['$field'],
            ];

            yield 'divide' => [
                'expected' => ['$divide' => ['$field', 5]],
                'operator' => 'divide',
                'args' => ['$field', 5],
            ];

            yield 'exp' => [
                'expected' => ['$exp' => '$field'],
                'operator' => 'exp',
                'args' => ['$field'],
            ];

            yield 'floor' => [
                'expected' => ['$floor' => '$field'],
                'operator' => 'floor',
                'args' => ['$field'],
            ];

            yield 'ln' => [
                'expected' => ['$ln' => '$field'],
                'operator' => 'ln',
                'args' => ['$field'],
            ];

            yield 'log' => [
                'expected' => ['$log' => ['$field', '$base']],
                'operator' => 'log',
                'args' => ['$field', '$base'],
            ];

            yield 'log10' => [
                'expected' => ['$log10' => '$field'],
                'operator' => 'log10',
                'args' => ['$field'],
            ];

            yield 'mod' => [
                'expected' => ['$mod' => ['$field', 5]],
                'operator' => 'mod',
                'args' => ['$field', 5],
            ];

            yield 'multiplyWithTwoArgs' => [
                'expected' => ['$multiply' => ['$field', 5]],
                'operator' => 'multiply',
                'args' => ['$field', 5],
            ];

            yield 'multiplyWithMultipleArgs' => [
                'expected' => ['$multiply' => ['$field', 5, '$otherField']],
                'operator' => 'multiply',
                'args' => ['$field', 5, '$otherField'],
            ];

            yield 'multiply' => [
                'expected' => ['$multiply' => ['$field', 5]],
                'operator' => 'multiply',
                'args' => ['$field', 5],
            ];

            yield 'pow' => [
                'expected' => ['$pow' => ['$number', '$exponent']],
                'operator' => 'pow',
                'args' => ['$number', '$exponent'],
            ];

            yield 'round' => [
                'expected' => ['$round' => ['$number', '$place']],
                'operator' => 'round',
                'args' => ['$number', '$place'],
            ];

            yield 'sortArray' => [
                'expected' => ['$sortArray' => ['input' => '$field', 'sortBy' => ['foo' => 1]]],
                'operator' => 'sortArray',
                'args' => ['$field', ['foo' => 1]],
            ];

            yield 'sqrt' => [
                'expected' => ['$sqrt' => '$field'],
                'operator' => 'sqrt',
                'args' => ['$field'],
            ];

            yield 'subtract' => [
                'expected' => ['$subtract' => ['$field', '$otherField']],
                'operator' => 'subtract',
                'args' => ['$field', '$otherField'],
            ];

            yield 'trunc' => [
                'expected' => ['$trunc' => '$field'],
                'operator' => 'trunc',
                'args' => ['$field'],
            ];
    }

    public static function provideArrayExpressionOperators(): Generator
    {
        yield 'arrayElemAt' => [
            'expected' => ['$arrayElemAt' => ['$array', '$index']],
            'operator' => 'arrayElemAt',
            'args' => ['$array', '$index'],
        ];

        yield 'arrayToObject' => [
            'expected' => ['$arrayToObject' => [['item', 'abc123'], ['qty', 25]]],
            'operator' => 'arrayToObject',
            'args' => [[['item', 'abc123'], ['qty', 25]]],
        ];

        yield 'concatArraysWithTwoArgs' => [
            'expected' => ['$concatArrays' => [[1, 2, 3], '$array1']],
            'operator' => 'concatArrays',
            'args' => [[1, 2, 3], '$array1'],
        ];

        yield 'concatArraysWithMultipleArgs' => [
            'expected' => ['$concatArrays' => [[1, 2, 3], '$array1', '$array2', [4, 5, 6]]],
            'operator' => 'concatArrays',
            'args' => [[1, 2, 3], '$array1', '$array2', [4, 5, 6]],
        ];

        yield 'filter' => [
            'expected' => ['$filter' => ['input' => '$array', 'as' => '$as', 'cond' => '$cond']],
            'operator' => 'filter',
            'args' => ['$array', '$as', '$cond'],
        ];

        yield 'first' => [
            'expected' => ['$first' => '$array'],
            'operator' => 'first',
            'args' => ['$array'],
        ];

        yield 'firstN' => [
            'expected' => ['$firstN' => ['input' => '$array', 'n' => '$n']],
            'operator' => 'firstN',
            'args' => ['$array', '$n'],
        ];

        yield 'in' => [
            'expected' => ['$in' => ['$field', '$otherField']],
            'operator' => 'in',
            'args' => ['$field', '$otherField'],
        ];

        yield 'indexOfArrayWithoutStartOrEnd' => [
            'expected' => ['$indexOfArray' => ['$field', '$otherField']],
            'operator' => 'indexOfArray',
            'args' => ['$field', '$otherField'],
        ];

        yield 'indexOfArrayWithoutStartWithEnd' => [
            'expected' => ['$indexOfArray' => ['$field', '$otherField']],
            'operator' => 'indexOfArray',
            'args' => ['$field', '$otherField', null, '$end'],
        ];

        yield 'indexOfArrayWithStart' => [
            'expected' => ['$indexOfArray' => ['$field', '$otherField', '$start']],
            'operator' => 'indexOfArray',
            'args' => ['$field', '$otherField', '$start'],
        ];

        yield 'indexOfArrayWithStartAndEnd' => [
            'expected' => ['$indexOfArray' => ['$field', '$otherField', '$start', '$end']],
            'operator' => 'indexOfArray',
            'args' => ['$field', '$otherField', '$start', '$end'],
        ];

        yield 'isArray' => [
            'expected' => ['$isArray' => '$field'],
            'operator' => 'isArray',
            'args' => ['$field'],
        ];

        yield 'last' => [
            'expected' => ['$last' => '$array'],
            'operator' => 'last',
            'args' => ['$array'],
        ];

        yield 'lastN' => [
            'expected' => ['$lastN' => ['input' => '$array', 'n' => '$n']],
            'operator' => 'lastN',
            'args' => ['$array', '$n'],
        ];

        yield 'map' => [
            'expected' => ['$map' => ['input' => '$quizzes', 'as' => 'grade', 'in' => ['$add' => ['$$grade', 2]]]],
            'operator' => 'map',
            'args' => static fn (Expr $expr) => [
                '$quizzes',
                'grade',
                $expr->add('$$grade', 2),
            ],
        ];

        yield 'maxN' => [
            'expected' => ['$maxN' => ['input' => '$array', 'n' => '$n']],
            'operator' => 'maxN',
            'args' => ['$array', '$n'],
        ];

        yield 'minN' => [
            'expected' => ['$minN' => ['input' => '$array', 'n' => '$n']],
            'operator' => 'minN',
            'args' => ['$array', '$n'],
        ];

        yield 'objectToArray' => [
            'expected' => ['$objectToArray' => ['$obj']],
            'operator' => 'objectToArray',
            'args' => [['$obj']],
        ];

        yield 'rangeWithoutStep' => [
            'expected' => ['$range' => ['$start', '$end']],
            'operator' => 'range',
            'args' => ['$start', '$end'],
        ];

        yield 'rangeWithStep' => [
            'expected' => ['$range' => ['$start', '$end', 5]],
            'operator' => 'range',
            'args' => ['$start', '$end', 5],
        ];

        yield 'reduce' => [
            'expected' => [
                '$reduce' => [
                    'input' => '$array',
                    'initialValue' => ['sum' => 0, 'product' => 1],
                    'in' => [
                        '$add' => ['$$value.sum', '$$this'],
                        '$multiply' => ['$$value.product', '$$this'],
                    ],
                ],
            ],
            'operator' => 'reduce',
            'args' => static fn (Expr $expr) => [
                '$array',
                ['sum' => 0, 'product' => 1],
                $expr
                        ->add('$$value.sum', '$$this')
                        ->multiply('$$value.product', '$$this'),
            ],
        ];

        yield 'reverseArray' => [
            'expected' => ['$reverseArray' => '$array'],
            'operator' => 'reverseArray',
            'args' => ['$array'],
        ];

        yield 'size' => [
            'expected' => ['$size' => '$field'],
            'operator' => 'size',
            'args' => ['$field'],
        ];

        yield 'sliceWithoutPosition' => [
            'expected' => ['$slice' => ['$array', '$n']],
            'operator' => 'slice',
            'args' => ['$array', '$n'],
        ];

        yield 'sliceWithPosition' => [
            'expected' => ['$slice' => ['$array', '$position', '$n']],
            'operator' => 'slice',
            'args' => ['$array', '$n', '$position'],
        ];

        yield 'zipWithoutExtraFields' => [
            'expected' => ['$zip' => ['inputs' => ['$array1', '$array2']]],
            'operator' => 'zip',
            'args' => [['$array1', '$array2']],
        ];

        yield 'zipWithUseLongestLengthWithoutDefault' => [
            'expected' => ['$zip' => ['inputs' => ['$array1', '$array2'], 'useLongestLength' => true]],
            'operator' => 'zip',
            'args' => [['$array1', '$array2'], true],
        ];

        yield 'zipWithUseLongestLengthAndDefault' => [
            'expected' => ['$zip' => ['inputs' => ['$array1', '$array2'], 'useLongestLength' => true, 'defaults' => ['a', 'b']]],
            'operator' => 'zip',
            'args' => [['$array1', '$array2'], true, ['a', 'b']],
        ];
    }

    public static function provideBooleanExpressionOperators(): Generator
    {
        yield 'and' => [
            'expected' => ['$and' => ['$field', '$otherField']],
            'operator' => 'and',
            'args' => ['$field', '$otherField'],
        ];

        yield 'or' => [
            'expected' => ['$or' => ['$field', '$otherField']],
            'operator' => 'or',
            'args' => ['$field', '$otherField'],
        ];

        yield 'not' => [
            'expected' => ['$not' => '$field'],
            'operator' => 'not',
            'args' => ['$field'],
        ];
    }

    public static function provideComparisonExpressionOperators(): Generator
    {
        yield 'cmp' => [
            'expected' => ['$cmp' => ['$field', '$otherField']],
            'operator' => 'cmp',
            'args' => ['$field', '$otherField'],
        ];

        yield 'eq' => [
            'expected' => ['$eq' => ['$field', '$otherField']],
            'operator' => 'eq',
            'args' => ['$field', '$otherField'],
        ];

        yield 'gt' => [
            'expected' => ['$gt' => ['$field', '$otherField']],
            'operator' => 'gt',
            'args' => ['$field', '$otherField'],
        ];

        yield 'gte' => [
            'expected' => ['$gte' => ['$field', '$otherField']],
            'operator' => 'gte',
            'args' => ['$field', '$otherField'],
        ];

        yield 'lt' => [
            'expected' => ['$lt' => ['$field', '$otherField']],
            'operator' => 'lt',
            'args' => ['$field', '$otherField'],
        ];

        yield 'lte' => [
            'expected' => ['$lte' => ['$field', '$otherField']],
            'operator' => 'lte',
            'args' => ['$field', '$otherField'],
        ];

        yield 'ne' => [
            'expected' => ['$ne' => ['$field', 5]],
            'operator' => 'ne',
            'args' => ['$field', 5],
        ];
    }

    public static function provideConditionalExpressionOperators(): Generator
    {
        yield 'cond' => [
            'expected' => ['$cond' => ['if' => ['$gte' => ['$field', 5]], 'then' => '$field', 'else' => '$otherField']],
            'operator' => 'cond',
            'args' => static fn (Expr $expr) => [
                $expr->gte('$field', 5),
                '$field',
                '$otherField',
            ],
        ];

        yield 'ifNull' => [
            'expected' => ['$ifNull' => ['$field', '$otherField']],
            'operator' => 'ifNull',
            'args' => ['$field', '$otherField'],
        ];
    }

    public static function provideCustomExpressionOperators(): Generator
    {
        yield 'accumulatorWithRequiredArgs' => [
            'expected' => [
                '$accumulator' => [
                    'init' => 'function() { return 0; }',
                    'accumulate' => 'function(state, value) { return state + value; }',
                    'accumulateArgs' => ['$value'],
                    'merge' => 'function(state1, state2) { return state1 + state2; }',
                    'lang' => 'js',
                ],
            ],
            'operator' => 'accumulator',
            'args' => [
                'function() { return 0; }',
                'function(state, value) { return state + value; }',
                ['$value'],
                'function(state1, state2) { return state1 + state2; }',
            ],
        ];

        yield 'accumulatorWithAllArgs' => [
            'expected' => [
                '$accumulator' => [
                    'init' => 'function(initial) { return initial; }',
                    'initArgs' => [1],
                    'accumulate' => 'function(state, value) { return state + value; }',
                    'accumulateArgs' => ['$value'],
                    'merge' => 'function(state1, state2) { return state1 + state2; }',
                    'finalize' => 'function(state) { return state; }',
                    'lang' => 'js',
                ],
            ],
            'operator' => 'accumulator',
            'args' => [
                'function(initial) { return initial; }',
                'function(state, value) { return state + value; }',
                ['$value'],
                'function(state1, state2) { return state1 + state2; }',
                [1],
                'function(state) { return state; }',
            ],
        ];

        yield 'function' => [
            'expected' => [
                '$function' => [
                    'body' => 'function(value) { return value; }',
                    'args' => ['$field'],
                    'lang' => 'js',
                ],
            ],
            'operator' => 'function',
            'args' => [
                'function(value) { return value; }',
                ['$field'],
            ],
        ];
    }

    public static function provideDataSizeExpressionOperators(): Generator
    {
        yield 'binarySize' => [
            'expected' => ['$binarySize' => '$field'],
            'operator' => 'binarySize',
            'args' => ['$field'],
        ];

        yield 'bsonSize' => [
            'expected' => ['$bsonSize' => '$field'],
            'operator' => 'bsonSize',
            'args' => ['$field'],
        ];
    }

    public static function provideDateExpressionOperators(): Generator
    {
        yield 'dateAdd' => [
            'expected' => ['$dateAdd' => ['startDate' => '$dateField', 'unit' => 'day', 'amount' => 1]],
            'operator' => 'dateAdd',
            'args' => ['$dateField', 'day', 1],
        ];

        yield 'dateAddWithTimezone' => [
            'expected' => ['$dateAdd' => ['startDate' => '$dateField', 'unit' => 'day', 'amount' => 1, 'timezone' => '$timezone']],
            'operator' => 'dateAdd',
            'args' => ['$dateField', 'day', 1, '$timezone'],
        ];

        yield 'dateDiff' => [
            'expected' => ['$dateDiff' => ['startDate' => '$dateField', 'endDate' => '$otherDateField', 'unit' => 'day']],
            'operator' => 'dateDiff',
            'args' => ['$dateField', '$otherDateField', 'day'],
        ];

        yield 'dateDiffWithTimezone' => [
            'expected' => ['$dateDiff' => ['startDate' => '$dateField', 'endDate' => '$otherDateField', 'unit' => 'day', 'timezone' => '$timezone']],
            'operator' => 'dateDiff',
            'args' => ['$dateField', '$otherDateField', 'day', '$timezone'],
        ];

        yield 'dateDiffWithTimezoneAndStartOfWeek' => [
            'expected' => ['$dateDiff' => ['startDate' => '$dateField', 'endDate' => '$otherDateField', 'unit' => 'day', 'timezone' => '$timezone', 'startOfWeek' => '$startOfWeek']],
            'operator' => 'dateDiff',
            'args' => ['$dateField', '$otherDateField', 'day', '$timezone', '$startOfWeek'],
        ];

        yield 'dateFromPartsYearOnly' => [
            'expected' => ['$dateFromParts' => ['year' => 2019]],
            'operator' => 'dateFromParts',
            'args' => [2019],
        ];

        yield 'dateFromPartsWithMonth' => [
            'expected' => ['$dateFromParts' => ['year' => 2019, 'month' => 3]],
            'operator' => 'dateFromParts',
            'args' => [2019, null, 3],
        ];

        yield 'dateFromPartsWithDay' => [
            'expected' => ['$dateFromParts' => ['year' => 2019, 'month' => 3, 'day' => 14]],
            'operator' => 'dateFromParts',
            'args' => [2019, null, 3, null, 14],
        ];

        yield 'dateFromPartsWithHour' => [
            'expected' => ['$dateFromParts' => ['year' => 2019, 'month' => 3, 'day' => 14, 'hour' => 10]],
            'operator' => 'dateFromParts',
            'args' => [2019, null, 3, null, 14, null, 10],
        ];

        yield 'dateFromPartsWithMinute' => [
            'expected' => ['$dateFromParts' => ['year' => 2019, 'month' => 3, 'day' => 14, 'hour' => 10, 'minute' => 13]],
            'operator' => 'dateFromParts',
            'args' => [2019, null, 3, null, 14, null, 10, 13],
        ];

        yield 'dateFromPartsWithSecond' => [
            'expected' => ['$dateFromParts' => ['year' => 2019, 'month' => 3, 'day' => 14, 'hour' => 10, 'minute' => 13, 'second' => 27]],
            'operator' => 'dateFromParts',
            'args' => [2019, null, 3, null, 14, null, 10, 13, 27],
        ];

        yield 'dateFromPartsWithMillisecond' => [
            'expected' => ['$dateFromParts' => ['year' => 2019, 'month' => 3, 'day' => 14, 'hour' => 10, 'minute' => 13, 'second' => 27, 'millisecond' => 123]],
            'operator' => 'dateFromParts',
            'args' => [2019, null, 3, null, 14, null, 10, 13, 27, 123],
        ];

        yield 'dateFromPartsWithTimezone' => [
            'expected' => ['$dateFromParts' => ['year' => 2019, 'month' => 3, 'day' => 14, 'hour' => 10, 'minute' => 13, 'second' => 27, 'millisecond' => 123, 'timezone' => '$timezone']],
            'operator' => 'dateFromParts',
            'args' => [2019, null, 3, null, 14, null, 10, 13, 27, 123, '$timezone'],
        ];

        yield 'dateFromPartsIsoWeekYearOnly' => [
            'expected' => ['$dateFromParts' => ['isoWeekYear' => 2020]],
            'operator' => 'dateFromParts',
            'args' => [null, 2020],
        ];

        yield 'dateFromPartsWithIsoWeek' => [
            'expected' => ['$dateFromParts' => ['isoWeekYear' => 2020, 'isoWeek' => 5]],
            'operator' => 'dateFromParts',
            'args' => [null, 2020, null, 5],
        ];

        yield 'dateFromPartsWithIsoDayOfWeek' => [
            'expected' => ['$dateFromParts' => ['isoWeekYear' => 2020, 'isoWeek' => 5, 'isoDayOfWeek' => 7]],
            'operator' => 'dateFromParts',
            'args' => [null, 2020, null, 5, null, 7],
        ];

        yield 'dateFromString' => [
            'expected' => ['$dateFromString' => ['dateString' => '2019-14-03']],
            'operator' => 'dateFromString',
            'args' => ['2019-14-03'],
        ];

        yield 'dateFromStringWithFormat' => [
            'expected' => ['$dateFromString' => ['dateString' => '2019-14-03', 'format' => '%Y-%m-%d']],
            'operator' => 'dateFromString',
            'args' => ['2019-14-03', '%Y-%m-%d'],
        ];

        yield 'dateFromStringWithTimezone' => [
            'expected' => ['$dateFromString' => ['dateString' => '2019-14-03', 'format' => '%Y-%m-%d', 'timezone' => '$timezone']],
            'operator' => 'dateFromString',
            'args' => ['2019-14-03', '%Y-%m-%d', '$timezone'],
        ];

        yield 'dateFromStringWithOnError' => [
            'expected' => ['$dateFromString' => ['dateString' => '2019-14-03', 'format' => '%Y-%m-%d', 'timezone' => '$timezone', 'onError' => '$defaultDate']],
            'operator' => 'dateFromString',
            'args' => ['2019-14-03', '%Y-%m-%d', '$timezone', '$defaultDate'],
        ];

        yield 'dateFromStringWithOnNull' => [
            'expected' => ['$dateFromString' => ['dateString' => '2019-14-03', 'format' => '%Y-%m-%d', 'timezone' => '$timezone', 'onError' => '$defaultDate', 'onNull' => '$defaultDate']],
            'operator' => 'dateFromString',
            'args' => ['2019-14-03', '%Y-%m-%d', '$timezone', '$defaultDate', '$defaultDate'],
        ];

        yield 'dateSubtract' => [
            'expected' => ['$dateSubtract' => ['startDate' => '$dateField', 'unit' => 'day', 'amount' => 1]],
            'operator' => 'dateSubtract',
            'args' => ['$dateField', 'day', 1],
        ];

        yield 'dateSubtractWithTimezone' => [
            'expected' => ['$dateSubtract' => ['startDate' => '$dateField', 'unit' => 'day', 'amount' => 1, 'timezone' => '$timezone']],
            'operator' => 'dateSubtract',
            'args' => ['$dateField', 'day', 1, '$timezone'],
        ];

        yield 'dateToParts' => [
            'expected' => ['$dateToParts' => ['date' => '$dateField']],
            'operator' => 'dateToParts',
            'args' => ['$dateField'],
        ];

        yield 'dateToPartsWithTimezone' => [
            'expected' => ['$dateToParts' => ['date' => '$dateField', 'timezone' => '$timezone']],
            'operator' => 'dateToParts',
            'args' => ['$dateField', '$timezone'],
        ];

        yield 'dateToPartsWithIso8601' => [
            'expected' => ['$dateToParts' => ['date' => '$dateField', 'timezone' => '$timezone', 'iso8601' => true]],
            'operator' => 'dateToParts',
            'args' => ['$dateField', '$timezone', true],
        ];

        yield 'dateToString' => [
            'expected' => ['$dateToString' => ['date' => '$dateField', 'format' => '%Y-%m-%d']],
            'operator' => 'dateToString',
            'args' => ['%Y-%m-%d', '$dateField'],
        ];

        yield 'dateToStringWithTimezone' => [
            'expected' => ['$dateToString' => ['date' => '$dateField', 'format' => '%Y-%m-%d', 'timezone' => '$timezone']],
            'operator' => 'dateToString',
            'args' => ['%Y-%m-%d', '$dateField', '$timezone'],
        ];

        yield 'dateToStringWithOnNull' => [
            'expected' => ['$dateToString' => ['date' => '$dateField', 'format' => '%Y-%m-%d', 'timezone' => '$timezone', 'onNull' => '$defaultDate']],
            'operator' => 'dateToString',
            'args' => ['%Y-%m-%d', '$dateField', '$timezone', '$defaultDate'],
        ];

        yield 'dateTrunc' => [
            'expected' => ['$dateTrunc' => ['date' => '$dateField', 'unit' => 'day']],
            'operator' => 'dateTrunc',
            'args' => ['$dateField', 'day'],
        ];

        yield 'dateTruncWithBinSize' => [
            'expected' => ['$dateTrunc' => ['date' => '$dateField', 'unit' => 'day', 'binSize' => 2]],
            'operator' => 'dateTrunc',
            'args' => ['$dateField', 'day', 2],
        ];

        yield 'dateTruncWithTimezone' => [
            'expected' => ['$dateTrunc' => ['date' => '$dateField', 'unit' => 'day', 'binSize' => 2, 'timezone' => '$timezone']],
            'operator' => 'dateTrunc',
            'args' => ['$dateField', 'day', 2, '$timezone'],
        ];

        yield 'dateTruncWithStartOfWeek' => [
            'expected' => ['$dateTrunc' => ['date' => '$dateField', 'unit' => 'day', 'binSize' => 2, 'timezone' => '$timezone', 'startOfWeek' => 'monday']],
            'operator' => 'dateTrunc',
            'args' => ['$dateField', 'day', 2, '$timezone', 'monday'],
        ];

        yield 'dayOfMonth' => [
            'expected' => ['$dayOfMonth' => '$dateField'],
            'operator' => 'dayOfMonth',
            'args' => ['$dateField'],
        ];

        yield 'dayOfWeek' => [
            'expected' => ['$dayOfWeek' => '$dateField'],
            'operator' => 'dayOfWeek',
            'args' => ['$dateField'],
        ];

        yield 'dayOfYear' => [
            'expected' => ['$dayOfYear' => '$dateField'],
            'operator' => 'dayOfYear',
            'args' => ['$dateField'],
        ];

        yield 'hour' => [
            'expected' => ['$hour' => '$dateField'],
            'operator' => 'hour',
            'args' => ['$dateField'],
        ];

        yield 'isoDayOfWeek' => [
            'expected' => ['$isoDayOfWeek' => '$dateField'],
            'operator' => 'isoDayOfWeek',
            'args' => ['$dateField'],
        ];

        yield 'isoWeek' => [
            'expected' => ['$isoWeek' => '$dateField'],
            'operator' => 'isoWeek',
            'args' => ['$dateField'],
        ];

        yield 'isoWeekYear' => [
            'expected' => ['$isoWeekYear' => '$dateField'],
            'operator' => 'isoWeekYear',
            'args' => ['$dateField'],
        ];

        yield 'millisecond' => [
            'expected' => ['$millisecond' => '$dateField'],
            'operator' => 'millisecond',
            'args' => ['$dateField'],
        ];

        yield 'minute' => [
            'expected' => ['$minute' => '$dateField'],
            'operator' => 'minute',
            'args' => ['$dateField'],
        ];

        yield 'month' => [
            'expected' => ['$month' => '$dateField'],
            'operator' => 'month',
            'args' => ['$dateField'],
        ];

        yield 'second' => [
            'expected' => ['$second' => '$dateField'],
            'operator' => 'second',
            'args' => ['$dateField'],
        ];

        yield 'week' => [
            'expected' => ['$week' => '$dateField'],
            'operator' => 'week',
            'args' => ['$dateField'],
        ];

        yield 'year' => [
            'expected' => ['$year' => '$dateField'],
            'operator' => 'year',
            'args' => ['$dateField'],
        ];
    }

    public static function provideGroupAccumulatorExpressionOperators(): Generator
    {
        yield 'addToSet (group)' => [
            'expected' => ['$addToSet' => '$field'],
            'operator' => 'addToSet',
            'args' => ['$field'],
        ];

        yield 'avg (group)' => [
            'expected' => ['$avg' => '$field'],
            'operator' => 'avg',
            'args' => ['$field'],
        ];

        yield 'bottom (group)' => [
            'expected' => ['$bottom' => ['output' => '$field', 'sortBy' => ['foo' => 1]]],
            'operator' => 'bottom',
            'args' => ['$field', ['foo' => 1]],
        ];

        yield 'bottomN (group)' => [
            'expected' => ['$bottomN' => ['output' => '$field', 'sortBy' => ['foo' => 1], 'n' => 5]],
            'operator' => 'bottomN',
            'args' => ['$field', ['foo' => 1], 5],
        ];

        yield 'count (group)' => [
            'expected' => ['$count' => []],
            'operator' => 'countDocuments',
            'args' => [],
        ];

        yield 'first (group)' => [
            'expected' => ['$first' => '$field'],
            'operator' => 'first',
            'args' => ['$field'],
        ];

        yield 'firstN (group)' => [
            'expected' => ['$firstN' => ['input' => '$field', 'n' => 5]],
            'operator' => 'firstN',
            'args' => ['$field', 5],
        ];

        yield 'last (group)' => [
            'expected' => ['$last' => '$field'],
            'operator' => 'last',
            'args' => ['$field'],
        ];

        yield 'lastN (group)' => [
            'expected' => ['$lastN' => ['input' => '$field', 'n' => 5]],
            'operator' => 'lastN',
            'args' => ['$field', 5],
        ];

        yield 'max (group)' => [
            'expected' => ['$max' => '$field'],
            'operator' => 'max',
            'args' => ['$field'],
        ];

        yield 'maxN (group)' => [
            'expected' => ['$maxN' => ['input' => '$field', 'n' => 5]],
            'operator' => 'maxN',
            'args' => ['$field', 5],
        ];

        yield 'min (group)' => [
            'expected' => ['$min' => '$field'],
            'operator' => 'min',
            'args' => ['$field'],
        ];

        yield 'minN (group)' => [
            'expected' => ['$minN' => ['input' => '$field', 'n' => 5]],
            'operator' => 'minN',
            'args' => ['$field', 5],
        ];

        yield 'push (group)' => [
            'expected' => ['$push' => '$field'],
            'operator' => 'push',
            'args' => ['$field'],
        ];

        yield 'stdDevPop (group)' => [
            'expected' => ['$stdDevPop' => '$field'],
            'operator' => 'stdDevPop',
            'args' => ['$field'],
        ];

        yield 'stdDevSamp (group)' => [
            'expected' => ['$stdDevSamp' => '$field'],
            'operator' => 'stdDevSamp',
            'args' => ['$field'],
        ];

        yield 'sum (group)' => [
            'expected' => ['$sum' => '$field'],
            'operator' => 'sum',
            'args' => ['$field'],
        ];

        yield 'top (group)' => [
            'expected' => ['$top' => ['output' => '$field', 'sortBy' => ['foo' => 1]]],
            'operator' => 'top',
            'args' => ['$field', ['foo' => 1]],
        ];

        yield 'topN (group)' => [
            'expected' => ['$topN' => ['output' => '$field', 'sortBy' => ['foo' => 1], 'n' => 5]],
            'operator' => 'topN',
            'args' => ['$field', ['foo' => 1], 5],
        ];
    }

    public static function provideMiscExpressionOperators(): Generator
    {
        yield 'let' => [
            'expected' => [
                '$let' => [
                    'vars' => [
                        'total' => ['$add' => ['$price', '$tax']],
                        'discounted' => ['$cond' => ['if' => '$applyDiscount', 'then' => 0.9, 'else' => 1]],
                    ],
                    'in' => ['$multiply' => ['$$total', '$$discounted']],
                ],
            ],
            'operator' => 'let',
            'args' => static fn (Expr $expr) => [
                $expr->expr()
                    ->field('total')
                    ->add('$price', '$tax')
                    ->field('discounted')
                    ->cond('$applyDiscount', 0.9, 1),
                $expr->expr()
                    ->multiply('$$total', '$$discounted'),
            ],
        ];

        yield 'literal' => [
            'expected' => ['$literal' => '$field'],
            'operator' => 'literal',
            'args' => ['$field'],
        ];

        yield 'meta' => [
            'expected' => ['$meta' => '$field'],
            'operator' => 'meta',
            'args' => ['$field'],
        ];

        yield 'rand' => [
            'expected' => ['$rand' => []],
            'operator' => 'rand',
            'args' => [],
        ];

        yield 'sampleRate' => [
            'expected' => ['$sampleRate' => 0.5],
            'operator' => 'sampleRate',
            'args' => [0.5],
        ];
    }

    public static function provideObjectExpressionOperators(): Generator
    {
        yield 'getField' => [
            'expected' => ['$getField' => ['field' => '$field', 'input' => '$obj']],
            'operator' => 'getField',
            'args' => ['$field', '$obj'],
        ];

        yield 'getFieldWithoutObject' => [
            'expected' => ['$getField' => ['field' => '$field']],
            'operator' => 'getField',
            'args' => ['$field'],
        ];

        yield 'mergeObjectsWithTwoArgs' => [
            'expected' => ['$mergeObjects' => ['$obj1', '$obj2']],
            'operator' => 'mergeObjects',
            'args' => ['$obj1', '$obj2'],
        ];

        yield 'mergeObjectsWithMultipleArgs' => [
            'expected' => ['$mergeObjects' => ['$obj1', '$obj2', '$obj3']],
            'operator' => 'mergeObjects',
            'args' => ['$obj1', '$obj2', '$obj3'],
        ];

        yield 'objectToArray' => [
            'expected' => ['$objectToArray' => ['$obj']],
            'operator' => 'objectToArray',
            'args' => [['$obj']],
        ];

        yield 'setField' => [
            'expected' => ['$setField' => ['field' => '$field', 'input' => '$obj', 'value' => 5]],
            'operator' => 'setField',
            'args' => ['$field', '$obj', 5],
        ];
    }

    public static function provideSetExpressionOperators(): Generator
    {
        yield 'allElementsTrue' => [
            'expected' => ['$allElementsTrue' => '$field'],
            'operator' => 'allElementsTrue',
            'args' => ['$field'],
        ];

        yield 'anyElementTrue' => [
            'expected' => ['$anyElementTrue' => '$field'],
            'operator' => 'anyElementTrue',
            'args' => ['$field'],
        ];

        yield 'setDifference' => [
            'expected' => ['$setDifference' => ['$field', '$otherField']],
            'operator' => 'setDifference',
            'args' => ['$field', '$otherField'],
        ];

        yield 'setEqualsWithTwoSets' => [
            'expected' => ['$setEquals' => ['$set1', '$set2']],
            'operator' => 'setEquals',
            'args' => ['$set1', '$set2'],
        ];

        yield 'setEqualsWithMultipleSets' => [
            'expected' => ['$setEquals' => ['$set1', '$set2', '$set3', '$set4']],
            'operator' => 'setEquals',
            'args' => ['$set1', '$set2', '$set3', '$set4'],
        ];

        yield 'setIntersectionWithTwoSets' => [
            'expected' => ['$setIntersection' => ['$set1', '$set2']],
            'operator' => 'setIntersection',
            'args' => ['$set1', '$set2'],
        ];

        yield 'setIntersectionWithMultipleSets' => [
            'expected' => ['$setIntersection' => ['$set1', '$set2', '$set3', '$set4']],
            'operator' => 'setIntersection',
            'args' => ['$set1', '$set2', '$set3', '$set4'],
        ];

        yield 'setIsSubset' => [
            'expected' => ['$setIsSubset' => ['$field', '$otherField']],
            'operator' => 'setIsSubset',
            'args' => ['$field', '$otherField'],
        ];

        yield 'setUnionWithTwoSets' => [
            'expected' => ['$setUnion' => ['$set1', '$set2']],
            'operator' => 'setUnion',
            'args' => ['$set1', '$set2'],
        ];

        yield 'setUnionWithMultipleSets' => [
            'expected' => ['$setUnion' => ['$set1', '$set2', '$set3', '$set4']],
            'operator' => 'setUnion',
            'args' => ['$set1', '$set2', '$set3', '$set4'],
        ];
    }

    public static function provideStringExpressionOperators(): Generator
    {
        yield 'concatWithTwoArgs' => [
            'expected' => ['$concat' => ['foo', '$field']],
            'operator' => 'concat',
            'args' => ['foo', '$field'],
        ];

        yield 'concatWithMultipleArgs' => [
            'expected' => ['$concat' => ['foo', '$field', '$otherField', 'bleh']],
            'operator' => 'concat',
            'args' => ['foo', '$field', '$otherField', 'bleh'],
        ];

        yield 'indexOfBytesWithoutStartOrEnd' => [
            'expected' => ['$indexOfBytes' => ['$field', '$otherField']],
            'operator' => 'indexOfBytes',
            'args' => ['$field', '$otherField'],
        ];

        yield 'indexOfBytesWithoutStartWithEnd' => [
            'expected' => ['$indexOfBytes' => ['$field', '$otherField']],
            'operator' => 'indexOfBytes',
            'args' => ['$field', '$otherField', null, '$end'],
        ];

        yield 'indexOfBytesWithStart' => [
            'expected' => ['$indexOfBytes' => ['$field', '$otherField', '$start']],
            'operator' => 'indexOfBytes',
            'args' => ['$field', '$otherField', '$start'],
        ];

        yield 'indexOfBytesWithStartAndEnd' => [
            'expected' => ['$indexOfBytes' => ['$field', '$otherField', '$start', '$end']],
            'operator' => 'indexOfBytes',
            'args' => ['$field', '$otherField', '$start', '$end'],
        ];

        yield 'indexOfCPWithoutStartOrEnd' => [
            'expected' => ['$indexOfCP' => ['$field', '$otherField']],
            'operator' => 'indexOfCP',
            'args' => ['$field', '$otherField'],
        ];

        yield 'indexOfCPWithoutStartWithEnd' => [
            'expected' => ['$indexOfCP' => ['$field', '$otherField']],
            'operator' => 'indexOfCP',
            'args' => ['$field', '$otherField', null, '$end'],
        ];

        yield 'indexOfCPWithStart' => [
            'expected' => ['$indexOfCP' => ['$field', '$otherField', '$start']],
            'operator' => 'indexOfCP',
            'args' => ['$field', '$otherField', '$start'],
        ];

        yield 'indexOfCPWithStartAndEnd' => [
            'expected' => ['$indexOfCP' => ['$field', '$otherField', '$start', '$end']],
            'operator' => 'indexOfCP',
            'args' => ['$field', '$otherField', '$start', '$end'],
        ];

        yield 'ltrim' => [
            'expected' => ['$ltrim' => ['$input', '$chars']],
            'operator' => 'ltrim',
            'args' => ['$input', '$chars'],
        ];

        yield 'regexFind' => [
            'expected' => ['$regexFind' => ['input' => '$input', 'regex' => '$regex']],
            'operator' => 'regexFind',
            'args' => ['$input', '$regex'],
        ];

        yield 'regexFindWithOptions' => [
            'expected' => ['$regexFind' => ['input' => '$input', 'regex' => '$regex', 'options' => 'i']],
            'operator' => 'regexFind',
            'args' => ['$input', '$regex', 'i'],
        ];

        yield 'regexFindAll' => [
            'expected' => ['$regexFindAll' => ['input' => '$input', 'regex' => '$regex']],
            'operator' => 'regexFindAll',
            'args' => ['$input', '$regex'],
        ];

        yield 'regexFindAllWithOptions' => [
            'expected' => ['$regexFindAll' => ['input' => '$input', 'regex' => '$regex', 'options' => 'i']],
            'operator' => 'regexFindAll',
            'args' => ['$input', '$regex', 'i'],
        ];

        yield 'regexMatch' => [
            'expected' => ['$regexMatch' => ['input' => '$input', 'regex' => '$regex']],
            'operator' => 'regexMatch',
            'args' => ['$input', '$regex'],
        ];

        yield 'regexMatchWithOptions' => [
            'expected' => ['$regexMatch' => ['input' => '$input', 'regex' => '$regex', 'options' => 'i']],
            'operator' => 'regexMatch',
            'args' => ['$input', '$regex', 'i'],
        ];

        yield 'replaceOne' => [
            'expected' => ['$replaceOne' => ['input' => '$input', 'find' => '$regex', 'replacement' => 'foo']],
            'operator' => 'replaceOne',
            'args' => ['$input', '$regex', 'foo'],
        ];

        yield 'replaceAll' => [
            'expected' => ['$replaceAll' => ['input' => '$input', 'find' => '$regex', 'replacement' => 'foo']],
            'operator' => 'replaceAll',
            'args' => ['$input', '$regex', 'foo'],
        ];

        yield 'rtrim' => [
            'expected' => ['$rtrim' => ['$input', '$chars']],
            'operator' => 'rtrim',
            'args' => ['$input', '$chars'],
        ];

        yield 'split' => [
            'expected' => ['$split' => ['$string', '$delimiter']],
            'operator' => 'split',
            'args' => ['$string', '$delimiter'],
        ];

        yield 'strcasecmp' => [
            'expected' => ['$strcasecmp' => ['$field', '$otherField']],
            'operator' => 'strcasecmp',
            'args' => ['$field', '$otherField'],
        ];

        yield 'strLenBytes' => [
            'expected' => ['$strLenBytes' => '$field'],
            'operator' => 'strLenBytes',
            'args' => ['$field'],
        ];

        yield 'strLenCP' => [
            'expected' => ['$strLenCP' => '$field'],
            'operator' => 'strLenCP',
            'args' => ['$field'],
        ];

        yield 'substr' => [
            'expected' => ['$substr' => ['$field', 0, '$length']],
            'operator' => 'substr',
            'args' => ['$field', 0, '$length'],
        ];

        yield 'substrBytes' => [
            'expected' => ['$substrBytes' => ['$field', 0, '$length']],
            'operator' => 'substrBytes',
            'args' => ['$field', 0, '$length'],
        ];

        yield 'substrCP' => [
            'expected' => ['$substrCP' => ['$field', 0, '$length']],
            'operator' => 'substrCP',
            'args' => ['$field', 0, '$length'],
        ];

        yield 'toLower' => [
            'expected' => ['$toLower' => '$field'],
            'operator' => 'toLower',
            'args' => ['$field'],
        ];

        yield 'toUpper' => [
            'expected' => ['$toUpper' => '$field'],
            'operator' => 'toUpper',
            'args' => ['$field'],
        ];

        yield 'trim' => [
            'expected' => ['$trim' => ['$input', '$chars']],
            'operator' => 'trim',
            'args' => ['$input', '$chars'],
        ];
    }

    public static function provideTimestampExpressionOperators(): Generator
    {
        yield 'tsIncrement' => [
            'expected' => ['$tsIncrement' => '$field'],
            'operator' => 'tsIncrement',
            'args' => ['$field'],
        ];

        yield 'tsSecond' => [
            'expected' => ['$tsSecond' => '$field'],
            'operator' => 'tsSecond',
            'args' => ['$field'],
        ];
    }

    public static function provideTrigonometryExpressionOperators(): Generator
    {
        yield 'sin' => [
            'expected' => ['$sin' => '$field'],
            'operator' => 'sin',
            'args' => ['$field'],
        ];

        yield 'cos' => [
            'expected' => ['$cos' => '$field'],
            'operator' => 'cos',
            'args' => ['$field'],
        ];

        yield 'tan' => [
            'expected' => ['$tan' => '$field'],
            'operator' => 'tan',
            'args' => ['$field'],
        ];

        yield 'asin' => [
            'expected' => ['$asin' => '$field'],
            'operator' => 'asin',
            'args' => ['$field'],
        ];

        yield 'acos' => [
            'expected' => ['$acos' => '$field'],
            'operator' => 'acos',
            'args' => ['$field'],
        ];

        yield 'atan' => [
            'expected' => ['$atan' => '$field'],
            'operator' => 'atan',
            'args' => ['$field'],
        ];

        yield 'atan2' => [
            'expected' => ['$atan2' => ['$expr1', '$expr2']],
            'operator' => 'atan2',
            'args' => ['$expr1', '$expr2'],
        ];

        yield 'sinh' => [
            'expected' => ['$sinh' => '$field'],
            'operator' => 'sinh',
            'args' => ['$field'],
        ];

        yield 'cosh' => [
            'expected' => ['$cosh' => '$field'],
            'operator' => 'cosh',
            'args' => ['$field'],
        ];

        yield 'tanh' => [
            'expected' => ['$tanh' => '$field'],
            'operator' => 'tanh',
            'args' => ['$field'],
        ];

        yield 'asinh' => [
            'expected' => ['$asinh' => '$field'],
            'operator' => 'asinh',
            'args' => ['$field'],
        ];

        yield 'acosh' => [
            'expected' => ['$acosh' => '$field'],
            'operator' => 'acosh',
            'args' => ['$field'],
        ];

        yield 'atanh' => [
            'expected' => ['$atanh' => '$field'],
            'operator' => 'atanh',
            'args' => ['$field'],
        ];

        yield 'degreesToRadians' => [
            'expected' => ['$degreesToRadians' => '$field'],
            'operator' => 'degreesToRadians',
            'args' => ['$field'],
        ];

        yield 'radiansToDegrees' => [
            'expected' => ['$radiansToDegrees' => '$field'],
            'operator' => 'radiansToDegrees',
            'args' => ['$field'],
        ];
    }

    public static function provideTypeExpressionOperators(): Generator
    {
        yield 'isArray' => [
            'expected' => ['$isArray' => '$field'],
            'operator' => 'isArray',
            'args' => ['$field'],
        ];

        yield 'type' => [
            'expected' => ['$type' => '$field'],
            'operator' => 'type',
            'args' => ['$field'],
        ];

        yield 'convert' => [
            'expected' => ['$convert' => ['input' => '$field', 'to' => '$to']],
            'operator' => 'convert',
            'args' => ['$field', '$to', null, null],
        ];

        yield 'isNumber' => [
            'expected' => ['$isNumber' => '$field'],
            'operator' => 'isNumber',
            'args' => ['$field'],
        ];

        yield 'toBool' => [
            'expected' => ['$toBool' => '$field'],
            'operator' => 'toBool',
            'args' => ['$field'],
        ];

        yield 'toDate' => [
            'expected' => ['$toDate' => '$field'],
            'operator' => 'toDate',
            'args' => ['$field'],
        ];

        yield 'toDecimal' => [
            'expected' => ['$toDecimal' => '$field'],
            'operator' => 'toDecimal',
            'args' => ['$field'],
        ];

        yield 'toDouble' => [
            'expected' => ['$toDouble' => '$field'],
            'operator' => 'toDouble',
            'args' => ['$field'],
        ];

        yield 'toInt' => [
            'expected' => ['$toInt' => '$field'],
            'operator' => 'toInt',
            'args' => ['$field'],
        ];

        yield 'toLong' => [
            'expected' => ['$toLong' => '$field'],
            'operator' => 'toLong',
            'args' => ['$field'],
        ];

        yield 'toObjectId' => [
            'expected' => ['$toObjectId' => '$field'],
            'operator' => 'toObjectId',
            'args' => ['$field'],
        ];

        yield 'toString' => [
            'expected' => ['$toString' => '$field'],
            'operator' => 'toString',
            'args' => ['$field'],
        ];
    }

    public static function provideWindowExpressionOperators(): Generator
    {
        yield 'covariancePop' => [
            'expected' => ['$covariancePop' => ['$field1', '$field2']],
            'operator' => 'covariancePop',
            'args' => ['$field1', '$field2'],
        ];

        yield 'covarianceSamp' => [
            'expected' => ['$covarianceSamp' => ['$field1', '$field2']],
            'operator' => 'covarianceSamp',
            'args' => ['$field1', '$field2'],
        ];

        yield 'denseRank' => [
            'expected' => ['$denseRank' => []],
            'operator' => 'denseRank',
            'args' => [],
        ];

        yield 'derivative' => [
            'expected' => ['$derivative' => ['input' => '$field', 'unit' => 'second']],
            'operator' => 'derivative',
            'args' => ['$field', 'second'],
        ];

        yield 'documentNumber' => [
            'expected' => ['$documentNumber' => []],
            'operator' => 'documentNumber',
            'args' => [],
        ];

        yield 'expMovingAvgWithN' => [
            'expected' => ['$expMovingAvg' => ['input' => '$field', 'N' => 5]],
            'operator' => 'expMovingAvg',
            'args' => ['$field', 5],
        ];

        yield 'expMovingAvgWithAlpha' => [
            'expected' => ['$expMovingAvg' => ['input' => '$field', 'alpha' => 0.5]],
            'operator' => 'expMovingAvg',
            'args' => ['$field', null, 0.5],
        ];

        yield 'integral' => [
            'expected' => ['$integral' => ['input' => '$field', 'unit' => 'second']],
            'operator' => 'integral',
            'args' => ['$field', 'second'],
        ];

        yield 'linearFill' => [
            'expected' => ['$linearFill' => '$field'],
            'operator' => 'linearFill',
            'args' => ['$field'],
        ];

        yield 'locf' => [
            'expected' => ['$locf' => '$field'],
            'operator' => 'locf',
            'args' => ['$field'],
        ];

        yield 'rank' => [
            'expected' => ['$rank' => []],
            'operator' => 'rank',
            'args' => [],
        ];

        yield 'shiftWithoutDefault' => [
            'expected' => ['$shift' => ['output' => '$field', 'by' => -1]],
            'operator' => 'shift',
            'args' => ['$field', -1],
        ];

        yield 'shiftWithDefault' => [
            'expected' => ['$shift' => ['output' => '$field', 'by' => -1, 'default' => '$defaultField']],
            'operator' => 'shift',
            'args' => ['$field', -1, '$defaultField'],
        ];
    }

    protected function createExpr(): Expr
    {
        return new Expr($this->dm, new ClassMetadata(User::class));
    }

    /**
     * @param Closure(Expr): mixed[]|mixed[] $args
     *
     * @return mixed[]
     */
    protected function resolveArgs($args): array
    {
        if (is_array($args)) {
            return $args;
        }

        if ($args instanceof Closure) {
            return $args($this->createExpr());
        }

        throw new InvalidArgumentException('Arguments for aggregation tests must be array or closure');
    }
}
