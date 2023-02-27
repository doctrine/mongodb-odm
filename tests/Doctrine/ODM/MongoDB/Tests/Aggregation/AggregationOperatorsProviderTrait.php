<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Documents\User;
use InvalidArgumentException;

use function is_array;

trait AggregationOperatorsProviderTrait
{
    public static function provideAllOperators(): array
    {
        return static::provideAccumulationOperators() + static::provideExpressionOperators();
    }

    public static function provideAccumulationOperators(): array
    {
        return [
            'addToSet' => [
                'expected' => ['$addToSet' => '$field'],
                'operator' => 'addToSet',
                'args' => ['$field'],
            ],
            'avg' => [
                'expected' => ['$avg' => '$field'],
                'operator' => 'avg',
                'args' => ['$field'],
            ],
            'first' => [
                'expected' => ['$first' => '$field'],
                'operator' => 'first',
                'args' => ['$field'],
            ],
            'last' => [
                'expected' => ['$last' => '$field'],
                'operator' => 'last',
                'args' => ['$field'],
            ],
            'max' => [
                'expected' => ['$max' => '$field'],
                'operator' => 'max',
                'args' => ['$field'],
            ],
            'min' => [
                'expected' => ['$min' => '$field'],
                'operator' => 'min',
                'args' => ['$field'],
            ],
            'push' => [
                'expected' => ['$push' => '$field'],
                'operator' => 'push',
                'args' => ['$field'],
            ],
            'stdDevPopWithTwoArrays' => [
                'expected' => ['$stdDevPop' => ['$array1', '$array2']],
                'operator' => 'stdDevPop',
                'args' => ['$array1', '$array2'],
            ],
            'stdDevPopWithMultipleArrays' => [
                'expected' => ['$stdDevPop' => ['$array1', '$array2', '$array3', '$array4']],
                'operator' => 'stdDevPop',
                'args' => ['$array1', '$array2', '$array3', '$array4'],
            ],
            'stdDevSampWithTwoArrays' => [
                'expected' => ['$stdDevSamp' => ['$array1', '$array2']],
                'operator' => 'stdDevSamp',
                'args' => ['$array1', '$array2'],
            ],
            'stdDevSampWithMultipleArrays' => [
                'expected' => ['$stdDevSamp' => ['$array1', '$array2', '$array3', '$array4']],
                'operator' => 'stdDevSamp',
                'args' => ['$array1', '$array2', '$array3', '$array4'],
            ],
            'sum' => [
                'expected' => ['$sum' => '$field'],
                'operator' => 'sum',
                'args' => ['$field'],
            ],
        ];
    }

    public static function provideExpressionOperators(): array
    {
        return [
            'abs' => [
                'expected' => ['$abs' => '$field'],
                'operator' => 'abs',
                'args' => ['$field'],
            ],
            'addWithTwoArgs' => [
                'expected' => ['$add' => [5, '$field']],
                'operator' => 'add',
                'args' => [5, '$field'],
            ],
            'addWithMultipleArgs' => [
                'expected' => ['$add' => [5, '$field', '$otherField', 4.99]],
                'operator' => 'add',
                'args' => [5, '$field', '$otherField', 4.99],
            ],
            'allElementsTrue' => [
                'expected' => ['$allElementsTrue' => '$field'],
                'operator' => 'allElementsTrue',
                'args' => ['$field'],
            ],
            'anyElementTrue' => [
                'expected' => ['$anyElementTrue' => '$field'],
                'operator' => 'anyElementTrue',
                'args' => ['$field'],
            ],
            'arrayElemAt' => [
                'expected' => ['$arrayElemAt' => ['$array', '$index']],
                'operator' => 'arrayElemAt',
                'args' => ['$array', '$index'],
            ],
            'ceil' => [
                'expected' => ['$ceil' => '$field'],
                'operator' => 'ceil',
                'args' => ['$field'],
            ],
            'cmp' => [
                'expected' => ['$cmp' => ['$field', '$otherField']],
                'operator' => 'cmp',
                'args' => ['$field', '$otherField'],
            ],
            'concatWithTwoArgs' => [
                'expected' => ['$concat' => ['foo', '$field']],
                'operator' => 'concat',
                'args' => ['foo', '$field'],
            ],
            'concatWithMultipleArgs' => [
                'expected' => ['$concat' => ['foo', '$field', '$otherField', 'bleh']],
                'operator' => 'concat',
                'args' => ['foo', '$field', '$otherField', 'bleh'],
            ],
            'concatArraysWithTwoArgs' => [
                'expected' => ['$concatArrays' => [[1, 2, 3], '$array1']],
                'operator' => 'concatArrays',
                'args' => [[1, 2, 3], '$array1'],
            ],
            'concatArraysWithMultipleArgs' => [
                'expected' => ['$concatArrays' => [[1, 2, 3], '$array1', '$array2', [4, 5, 6]]],
                'operator' => 'concatArrays',
                'args' => [[1, 2, 3], '$array1', '$array2', [4, 5, 6]],
            ],
            'cond' => [
                'expected' => ['$cond' => ['if' => ['$gte' => ['$field', 5]], 'then' => '$field', 'else' => '$otherField']],
                'operator' => 'cond',
                'args' => static fn (Expr $expr) => [
                    $expr->gte('$field', 5),
                    '$field',
                    '$otherField',
                ],
            ],
            'dateToString' => [
                'expected' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$dateField']],
                'operator' => 'dateToString',
                'args' => ['%Y-%m-%d', '$dateField'],
            ],
            'dayOfMonth' => [
                'expected' => ['$dayOfMonth' => '$dateField'],
                'operator' => 'dayOfMonth',
                'args' => ['$dateField'],
            ],
            'dayOfWeek' => [
                'expected' => ['$dayOfWeek' => '$dateField'],
                'operator' => 'dayOfWeek',
                'args' => ['$dateField'],
            ],
            'dayOfYear' => [
                'expected' => ['$dayOfYear' => '$dateField'],
                'operator' => 'dayOfYear',
                'args' => ['$dateField'],
            ],
            'divide' => [
                'expected' => ['$divide' => ['$field', 5]],
                'operator' => 'divide',
                'args' => ['$field', 5],
            ],
            'eq' => [
                'expected' => ['$eq' => ['$field', '$otherField']],
                'operator' => 'eq',
                'args' => ['$field', '$otherField'],
            ],
            'exp' => [
                'expected' => ['$exp' => '$field'],
                'operator' => 'exp',
                'args' => ['$field'],
            ],
            'filter' => [
                'expected' => ['$filter' => ['input' => '$array', 'as' => '$as', 'cond' => '$cond']],
                'operator' => 'filter',
                'args' => ['$array', '$as', '$cond'],
            ],
            'floor' => [
                'expected' => ['$floor' => '$field'],
                'operator' => 'floor',
                'args' => ['$field'],
            ],
            'gt' => [
                'expected' => ['$gt' => ['$field', '$otherField']],
                'operator' => 'gt',
                'args' => ['$field', '$otherField'],
            ],
            'gte' => [
                'expected' => ['$gte' => ['$field', '$otherField']],
                'operator' => 'gte',
                'args' => ['$field', '$otherField'],
            ],
            'hour' => [
                'expected' => ['$hour' => '$dateField'],
                'operator' => 'hour',
                'args' => ['$dateField'],
            ],
            'ifNull' => [
                'expected' => ['$ifNull' => ['$field', '$otherField']],
                'operator' => 'ifNull',
                'args' => ['$field', '$otherField'],
            ],
            'in' => [
                'expected' => ['$in' => ['$field', '$otherField']],
                'operator' => 'in',
                'args' => ['$field', '$otherField'],
            ],
            'indexOfArrayWithoutStartOrEnd' => [
                'expected' => ['$indexOfArray' => ['$field', '$otherField']],
                'operator' => 'indexOfArray',
                'args' => ['$field', '$otherField'],
            ],
            'indexOfArrayWithoutStartWithEnd' => [
                'expected' => ['$indexOfArray' => ['$field', '$otherField']],
                'operator' => 'indexOfArray',
                'args' => ['$field', '$otherField', null, '$end'],
            ],
            'indexOfArrayWithStart' => [
                'expected' => ['$indexOfArray' => ['$field', '$otherField', '$start']],
                'operator' => 'indexOfArray',
                'args' => ['$field', '$otherField', '$start'],
            ],
            'indexOfArrayWithStartAndEnd' => [
                'expected' => ['$indexOfArray' => ['$field', '$otherField', '$start', '$end']],
                'operator' => 'indexOfArray',
                'args' => ['$field', '$otherField', '$start', '$end'],
            ],
            'indexOfBytesWithoutStartOrEnd' => [
                'expected' => ['$indexOfBytes' => ['$field', '$otherField']],
                'operator' => 'indexOfBytes',
                'args' => ['$field', '$otherField'],
            ],
            'indexOfBytesWithoutStartWithEnd' => [
                'expected' => ['$indexOfBytes' => ['$field', '$otherField']],
                'operator' => 'indexOfBytes',
                'args' => ['$field', '$otherField', null, '$end'],
            ],
            'indexOfBytesWithStart' => [
                'expected' => ['$indexOfBytes' => ['$field', '$otherField', '$start']],
                'operator' => 'indexOfBytes',
                'args' => ['$field', '$otherField', '$start'],
            ],
            'indexOfBytesWithStartAndEnd' => [
                'expected' => ['$indexOfBytes' => ['$field', '$otherField', '$start', '$end']],
                'operator' => 'indexOfBytes',
                'args' => ['$field', '$otherField', '$start', '$end'],
            ],
            'indexOfCPWithoutStartOrEnd' => [
                'expected' => ['$indexOfCP' => ['$field', '$otherField']],
                'operator' => 'indexOfCP',
                'args' => ['$field', '$otherField'],
            ],
            'indexOfCPWithoutStartWithEnd' => [
                'expected' => ['$indexOfCP' => ['$field', '$otherField']],
                'operator' => 'indexOfCP',
                'args' => ['$field', '$otherField', null, '$end'],
            ],
            'indexOfCPWithStart' => [
                'expected' => ['$indexOfCP' => ['$field', '$otherField', '$start']],
                'operator' => 'indexOfCP',
                'args' => ['$field', '$otherField', '$start'],
            ],
            'indexOfCPWithStartAndEnd' => [
                'expected' => ['$indexOfCP' => ['$field', '$otherField', '$start', '$end']],
                'operator' => 'indexOfCP',
                'args' => ['$field', '$otherField', '$start', '$end'],
            ],
            'isArray' => [
                'expected' => ['$isArray' => '$field'],
                'operator' => 'isArray',
                'args' => ['$field'],
            ],
            'isoDayOfWeek' => [
                'expected' => ['$isoDayOfWeek' => '$dateField'],
                'operator' => 'isoDayOfWeek',
                'args' => ['$dateField'],
            ],
            'isoWeek' => [
                'expected' => ['$isoWeek' => '$dateField'],
                'operator' => 'isoWeek',
                'args' => ['$dateField'],
            ],
            'isoWeekYear' => [
                'expected' => ['$isoWeekYear' => '$dateField'],
                'operator' => 'isoWeekYear',
                'args' => ['$dateField'],
            ],
            'let' => [
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
            ],
            'literal' => [
                'expected' => ['$literal' => '$field'],
                'operator' => 'literal',
                'args' => ['$field'],
            ],
            'ln' => [
                'expected' => ['$ln' => '$field'],
                'operator' => 'ln',
                'args' => ['$field'],
            ],
            'log' => [
                'expected' => ['$log' => ['$field', '$base']],
                'operator' => 'log',
                'args' => ['$field', '$base'],
            ],
            'log10' => [
                'expected' => ['$log10' => '$field'],
                'operator' => 'log10',
                'args' => ['$field'],
            ],
            'lt' => [
                'expected' => ['$lt' => ['$field', '$otherField']],
                'operator' => 'lt',
                'args' => ['$field', '$otherField'],
            ],
            'lte' => [
                'expected' => ['$lte' => ['$field', '$otherField']],
                'operator' => 'lte',
                'args' => ['$field', '$otherField'],
            ],
            'map' => [
                'expected' => ['$map' => ['input' => '$quizzes', 'as' => 'grade', 'in' => ['$add' => ['$$grade', 2]]]],
                'operator' => 'map',
                'args' => static fn (Expr $expr) => [
                    '$quizzes',
                    'grade',
                    $expr->add('$$grade', 2),
                ],
            ],
            'meta' => [
                'expected' => ['$meta' => '$field'],
                'operator' => 'meta',
                'args' => ['$field'],
            ],
            'millisecond' => [
                'expected' => ['$millisecond' => '$dateField'],
                'operator' => 'millisecond',
                'args' => ['$dateField'],
            ],
            'minute' => [
                'expected' => ['$minute' => '$dateField'],
                'operator' => 'minute',
                'args' => ['$dateField'],
            ],
            'mod' => [
                'expected' => ['$mod' => ['$field', 5]],
                'operator' => 'mod',
                'args' => ['$field', 5],
            ],
            'month' => [
                'expected' => ['$month' => '$dateField'],
                'operator' => 'month',
                'args' => ['$dateField'],
            ],
            'multiply' => [
                'expected' => ['$multiply' => ['$field', 5]],
                'operator' => 'multiply',
                'args' => ['$field', 5],
            ],
            'ne' => [
                'expected' => ['$ne' => ['$field', 5]],
                'operator' => 'ne',
                'args' => ['$field', 5],
            ],
            'not' => [
                'expected' => ['$not' => '$field'],
                'operator' => 'not',
                'args' => ['$field'],
            ],
            'pow' => [
                'expected' => ['$pow' => ['$number', '$exponent']],
                'operator' => 'pow',
                'args' => ['$number', '$exponent'],
            ],
            'rangeWithoutStep' => [
                'expected' => ['$range' => ['$start', '$end', 1]],
                'operator' => 'range',
                'args' => ['$start', '$end'],
            ],
            'rangeWithStep' => [
                'expected' => ['$range' => ['$start', '$end', 5]],
                'operator' => 'range',
                'args' => ['$start', '$end', 5],
            ],
            'reduce' => [
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
            ],
            'reverseArray' => [
                'expected' => ['$reverseArray' => '$array'],
                'operator' => 'reverseArray',
                'args' => ['$array'],
            ],
            'second' => [
                'expected' => ['$second' => '$dateField'],
                'operator' => 'second',
                'args' => ['$dateField'],
            ],
            'setDifference' => [
                'expected' => ['$setDifference' => ['$field', '$otherField']],
                'operator' => 'setDifference',
                'args' => ['$field', '$otherField'],
            ],
            'setEqualsWithTwoSets' => [
                'expected' => ['$setEquals' => ['$set1', '$set2']],
                'operator' => 'setEquals',
                'args' => ['$set1', '$set2'],
            ],
            'setEqualsWithMultipleSets' => [
                'expected' => ['$setEquals' => ['$set1', '$set2', '$set3', '$set4']],
                'operator' => 'setEquals',
                'args' => ['$set1', '$set2', '$set3', '$set4'],
            ],
            'setIntersectionWithTwoSets' => [
                'expected' => ['$setIntersection' => ['$set1', '$set2']],
                'operator' => 'setIntersection',
                'args' => ['$set1', '$set2'],
            ],
            'setIntersectionWithMultipleSets' => [
                'expected' => ['$setIntersection' => ['$set1', '$set2', '$set3', '$set4']],
                'operator' => 'setIntersection',
                'args' => ['$set1', '$set2', '$set3', '$set4'],
            ],
            'setIsSubset' => [
                'expected' => ['$setIsSubset' => ['$field', '$otherField']],
                'operator' => 'setIsSubset',
                'args' => ['$field', '$otherField'],
            ],
            'setUnionWithTwoSets' => [
                'expected' => ['$setUnion' => ['$set1', '$set2']],
                'operator' => 'setUnion',
                'args' => ['$set1', '$set2'],
            ],
            'setUnionWithMultipleSets' => [
                'expected' => ['$setUnion' => ['$set1', '$set2', '$set3', '$set4']],
                'operator' => 'setUnion',
                'args' => ['$set1', '$set2', '$set3', '$set4'],
            ],
            'size' => [
                'expected' => ['$size' => '$field'],
                'operator' => 'size',
                'args' => ['$field'],
            ],
            'sliceWithoutPosition' => [
                'expected' => ['$slice' => ['$array', '$n']],
                'operator' => 'slice',
                'args' => ['$array', '$n'],
            ],
            'sliceWithPosition' => [
                'expected' => ['$slice' => ['$array', '$position', '$n']],
                'operator' => 'slice',
                'args' => ['$array', '$n', '$position'],
            ],
            'split' => [
                'expected' => ['$split' => ['$string', '$delimiter']],
                'operator' => 'split',
                'args' => ['$string', '$delimiter'],
            ],
            'sqrt' => [
                'expected' => ['$sqrt' => '$field'],
                'operator' => 'sqrt',
                'args' => ['$field'],
            ],
            'strcasecmp' => [
                'expected' => ['$strcasecmp' => ['$field', '$otherField']],
                'operator' => 'strcasecmp',
                'args' => ['$field', '$otherField'],
            ],
            'strLenBytes' => [
                'expected' => ['$strLenBytes' => '$field'],
                'operator' => 'strLenBytes',
                'args' => ['$field'],
            ],
            'strLenCP' => [
                'expected' => ['$strLenCP' => '$field'],
                'operator' => 'strLenCP',
                'args' => ['$field'],
            ],
            'substr' => [
                'expected' => ['$substr' => ['$field', 0, '$length']],
                'operator' => 'substr',
                'args' => ['$field', 0, '$length'],
            ],
            'substrBytes' => [
                'expected' => ['$substrBytes' => ['$field', 0, '$length']],
                'operator' => 'substrBytes',
                'args' => ['$field', 0, '$length'],
            ],
            'substrCP' => [
                'expected' => ['$substrCP' => ['$field', 0, '$length']],
                'operator' => 'substrCP',
                'args' => ['$field', 0, '$length'],
            ],
            'subtract' => [
                'expected' => ['$subtract' => ['$field', '$otherField']],
                'operator' => 'subtract',
                'args' => ['$field', '$otherField'],
            ],
            'toLower' => [
                'expected' => ['$toLower' => '$field'],
                'operator' => 'toLower',
                'args' => ['$field'],
            ],
            'toUpper' => [
                'expected' => ['$toUpper' => '$field'],
                'operator' => 'toUpper',
                'args' => ['$field'],
            ],
            'trunc' => [
                'expected' => ['$trunc' => '$field'],
                'operator' => 'trunc',
                'args' => ['$field'],
            ],
            'type' => [
                'expected' => ['$type' => '$field'],
                'operator' => 'type',
                'args' => ['$field'],
            ],
            'week' => [
                'expected' => ['$week' => '$dateField'],
                'operator' => 'week',
                'args' => ['$dateField'],
            ],
            'year' => [
                'expected' => ['$year' => '$dateField'],
                'operator' => 'year',
                'args' => ['$dateField'],
            ],
            'zipWithoutExtraFields' => [
                'expected' => ['$zip' => ['inputs' => ['$array1', '$array2']]],
                'operator' => 'zip',
                'args' => [['$array1', '$array2']],
            ],
            'zipWithUseLongestLengthWithoutDefault' => [
                'expected' => ['$zip' => ['inputs' => ['$array1', '$array2'], 'useLongestLength' => true]],
                'operator' => 'zip',
                'args' => [['$array1', '$array2'], true],
            ],
            'zipWithUseLongestLengthAndDefault' => [
                'expected' => ['$zip' => ['inputs' => ['$array1', '$array2'], 'useLongestLength' => true, 'defaults' => ['a', 'b']]],
                'operator' => 'zip',
                'args' => [['$array1', '$array2'], true, ['a', 'b']],
            ],
            'arrayToObject' => [
                'expected' => ['$arrayToObject' => ['$array']],
                'operator' => 'arrayToObject',
                'args' => [['$array']],
            ],
            'objectToArray' => [
                'expected' => ['$objectToArray' => ['$obj']],
                'operator' => 'objectToArray',
                'args' => [['$obj']],
            ],
            'round' => [
                'expected' => ['$round' => ['$number', '$place']],
                'operator' => 'round',
                'args' => ['$number', '$place'],
            ],
            'ltrim' => [
                'expected' => ['$ltrim' => ['$input', '$chars']],
                'operator' => 'ltrim',
                'args' => ['$input', '$chars'],
            ],
            'rtrim' => [
                'expected' => ['$rtrim' => ['$input', '$chars']],
                'operator' => 'rtrim',
                'args' => ['$input', '$chars'],
            ],
            'trim' => [
                'expected' => ['$trim' => ['$input', '$chars']],
                'operator' => 'trim',
                'args' => ['$input', '$chars'],
            ],
            'sin' => [
                'expected' => ['$sin' => '$field'],
                'operator' => 'sin',
                'args' => ['$field'],
            ],
            'cos' => [
                'expected' => ['$cos' => '$field'],
                'operator' => 'cos',
                'args' => ['$field'],
            ],
            'tan' => [
                'expected' => ['$tan' => '$field'],
                'operator' => 'tan',
                'args' => ['$field'],
            ],
            'asin' => [
                'expected' => ['$asin' => '$field'],
                'operator' => 'asin',
                'args' => ['$field'],
            ],
            'acos' => [
                'expected' => ['$acos' => '$field'],
                'operator' => 'acos',
                'args' => ['$field'],
            ],
            'atan' => [
                'expected' => ['$atan' => '$field'],
                'operator' => 'atan',
                'args' => ['$field'],
            ],
            'atan2' => [
                'expected' => ['$atan2' => ['$expr1', '$expr2']],
                'operator' => 'atan2',
                'args' => ['$expr1', '$expr2'],
            ],
            'sinh' => [
                'expected' => ['$sinh' => '$field'],
                'operator' => 'sinh',
                'args' => ['$field'],
            ],
            'cosh' => [
                'expected' => ['$cosh' => '$field'],
                'operator' => 'cosh',
                'args' => ['$field'],
            ],
            'tanh' => [
                'expected' => ['$tanh' => '$field'],
                'operator' => 'tanh',
                'args' => ['$field'],
            ],
            'degreesToRadians' => [
                'expected' => ['$degreesToRadians' => '$field'],
                'operator' => 'degreesToRadians',
                'args' => ['$field'],
            ],
            'radiansToDegrees' => [
                'expected' => ['$radiansToDegrees' => '$field'],
                'operator' => 'radiansToDegrees',
                'args' => ['$field'],
            ],
            'convert' => [
                'expected' => ['$convert' => ['input' => '$field', 'to' => '$to']],
                'operator' => 'convert',
                'args' => ['$field', '$to', null, null],
            ],
            'isNumber' => [
                'expected' => ['$isNumber' => '$field'],
                'operator' => 'isNumber',
                'args' => ['$field'],
            ],
            'toBool' => [
                'expected' => ['$toBool' => '$field'],
                'operator' => 'toBool',
                'args' => ['$field'],
            ],
            'toDate' => [
                'expected' => ['$toDate' => '$field'],
                'operator' => 'toDate',
                'args' => ['$field'],
            ],
            'toDecimal' => [
                'expected' => ['$toDecimal' => '$field'],
                'operator' => 'toDecimal',
                'args' => ['$field'],
            ],
            'toDouble' => [
                'expected' => ['$toDouble' => '$field'],
                'operator' => 'toDouble',
                'args' => ['$field'],
            ],
            'toInt' => [
                'expected' => ['$toInt' => '$field'],
                'operator' => 'toInt',
                'args' => ['$field'],
            ],
            'toLong' => [
                'expected' => ['$toLong' => '$field'],
                'operator' => 'toLong',
                'args' => ['$field'],
            ],
            'toObjectId' => [
                'expected' => ['$toObjectId' => '$field'],
                'operator' => 'toObjectId',
                'args' => ['$field'],
            ],
            'toString' => [
                'expected' => ['$toString' => '$field'],
                'operator' => 'toString',
                'args' => ['$field'],
            ],
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
