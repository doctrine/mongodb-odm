<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\AbstractSearchOperator;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\CompoundSearchOperatorInterface;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Generator;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\Constraint\IsInstanceOf;

use function array_combine;
use function array_map;
use function array_merge;

class SearchTest extends BaseTest
{
    use AggregationTestTrait;

    public static function provideAutocompleteBuilders(): Generator
    {
        yield 'Autocomplete required only' => [
            'expectedOperator' => [
                'autocomplete' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => 'content',
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->autocomplete('content', 'MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content');
            },
        ];

        yield 'Autocomplete with token order' => [
            'expectedOperator' => [
                'autocomplete' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => 'content',
                    'tokenOrder' => 'any',
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->autocomplete()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->tokenOrder('any');
            },
        ];

        yield 'Autocomplete with boost score' => [
            'expectedOperator' => [
                'autocomplete' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => 'content',
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->autocomplete()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->boostScore(1.5);
            },
        ];

        yield 'Autocomplete with constant score' => [
            'expectedOperator' => [
                'autocomplete' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => 'content',
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->autocomplete()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->constantScore(1.5);
            },
        ];

        yield 'Autocomplete with fuzzy search' => [
            'expectedOperator' => [
                'autocomplete' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => 'content',
                    'fuzzy' => (object) [
                        'maxEdits' => 1,
                        'prefixLength' => 2,
                        'maxExpansions' => 3,
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->autocomplete()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->fuzzy(1, 2, 3);
            },
        ];
    }

    public static function provideCompoundBuilders(): Generator
    {
        yield 'Compound with single must clause' => [
            'expectedOperator' => [
                'compound' => (object) [
                    'must' => [
                        (object) [
                            'text' => (object) [
                                'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                                'path' => ['items.content'],
                                'synonyms' => 'mySynonyms',
                            ],
                        ],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->compound()
                    ->must()
                        ->text()
                            ->path('items.content')
                            ->query('MongoDB', 'Aggregation', 'Pipeline')
                            ->synonyms('mySynonyms');
            },
        ];

        yield 'Compound with multiple must clauses' => [
            'expectedOperator' => [
                'compound' => (object) [
                    'must' => [
                        (object) [
                            'text' => (object) [
                                'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                                'path' => ['items.content'],
                                'synonyms' => 'mySynonyms',
                            ],
                        ],
                        (object) [
                            'near' => (object) [
                                'origin' => 5,
                                'pivot' => 3,
                                'path' => ['value1', 'value2'],
                            ],
                        ],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->compound()
                    ->must()
                        ->text()
                            ->path('items.content')
                            ->query('MongoDB', 'Aggregation', 'Pipeline')
                            ->synonyms('mySynonyms')
                        ->near(5, 3, 'value1', 'value2');
            },
        ];

        yield 'Compound with must and mustNot clauses' => [
            'expectedOperator' => [
                'compound' => (object) [
                    'must' => [
                        (object) [
                            'text' => (object) [
                                'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                                'path' => ['items.content'],
                                'synonyms' => 'mySynonyms',
                            ],
                        ],
                    ],
                    'mustNot' => [
                        (object) [
                            'exists' => (object) ['path' => 'hidden'],
                        ],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->compound()
                    ->must()
                        ->text()
                            ->path('items.content')
                            ->query('MongoDB', 'Aggregation', 'Pipeline')
                            ->synonyms('mySynonyms')
                    ->mustNot()
                        ->exists('hidden');
            },
        ];
    }

    public static function provideEmbeddedDocumentBuilders(): Generator
    {
        yield 'EmbeddedDocument with single text operator' => [
            'expectedOperator' => [
                'embeddedDocument' => (object) [
                    'path' => 'items',
                    'operator' => (object) [
                        'text' => (object) [
                            'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                            'path' => ['items.content'],
                            'synonyms' => 'mySynonyms',
                        ],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->embeddedDocument('items')
                    ->text()
                        ->path('items.content')
                        ->query('MongoDB', 'Aggregation', 'Pipeline')
                        ->synonyms('mySynonyms');
            },
        ];
    }

    public static function provideEmbeddedDocumentCompoundBuilders(): Generator
    {
        yield 'EmbeddedDocument with compound operator' => [
            'expectedOperator' => [
                'embeddedDocument' => (object) [
                    'path' => 'items',
                    'operator' => (object) [
                        'compound' => (object) [
                            'must' => [
                                (object) [
                                    'text' => (object) [
                                        'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                                        'path' => ['items.content'],
                                        'synonyms' => 'mySynonyms',
                                    ],
                                ],
                                (object) [
                                    'near' => (object) [
                                        'origin' => 5,
                                        'pivot' => 3,
                                        'path' => ['items.value1'],
                                    ],
                                ],
                            ],
                            'mustNot' => [
                                (object) [
                                    'exists' => (object) ['path' => 'items.hidden'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->embeddedDocument('items')
                    ->compound()
                        ->must()
                            ->text()
                                ->path('items.content')
                                ->query('MongoDB', 'Aggregation', 'Pipeline')
                                ->synonyms('mySynonyms')
                            ->near(5, 3, 'items.value1')
                        ->mustNot()
                            ->exists('items.hidden');
            },
        ];
    }

    public static function provideEqualsBuilders(): Generator
    {
        yield 'Equals required only' => [
            'expectedOperator' => [
                'equals' => (object) [
                    'path' => 'content',
                    'value' => 'MongoDB Aggregation Pipeline',
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->equals('content', 'MongoDB Aggregation Pipeline');
            },
        ];

        yield 'Equals with boost score' => [
            'expectedOperator' => [
                'equals' => (object) [
                    'path' => 'content',
                    'value' => 'MongoDB Aggregation Pipeline',
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->equals()
                    ->path('content')
                    ->value('MongoDB Aggregation Pipeline')
                    ->boostScore(1.5);
            },
        ];

        yield 'Equals with constant score' => [
            'expectedOperator' => [
                'equals' => (object) [
                    'path' => 'content',
                    'value' => 'MongoDB Aggregation Pipeline',
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->equals()
                    ->path('content')
                    ->value('MongoDB Aggregation Pipeline')
                    ->constantScore(1.5);
            },
        ];
    }

    public static function provideExistsBuilders(): Generator
    {
        yield 'Exists required only' => [
            'expectedOperator' => [
                'exists' => (object) ['path' => 'content'],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->exists('content');
            },
        ];
    }

    public static function provideGeoShapeBuilders(): Generator
    {
        yield 'CompoundedGeoShape required only' => [
            'expectedOperator' => [
                'geoShape' => (object) [
                    'path' => ['location1', 'location2'],
                    'relation' => 'contains',
                    'geometry' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoShape(
                    new Point([12.345, 23.456]),
                    'contains',
                    'location1',
                    'location2',
                );
            },
        ];

        yield 'CompoundedGeoShape with boost score' => [
            'expectedOperator' => [
                'geoShape' => (object) [
                    'path' => ['location'],
                    'relation' => 'contains',
                    'geometry' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoShape()
                    ->path('location')
                    ->relation('contains')
                    ->geometry(new Point([12.345, 23.456]))
                    ->boostScore(1.5);
            },
        ];

        yield 'CompoundedGeoShape with constant score' => [
            'expectedOperator' => [
                'geoShape' => (object) [
                    'path' => ['location'],
                    'relation' => 'contains',
                    'geometry' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoShape()
                    ->path('location')
                    ->relation('contains')
                    ->geometry(new Point([12.345, 23.456]))
                    ->constantScore(1.5);
            },
        ];
    }

    public static function provideGeoWithinBuilders(): Generator
    {
        yield 'GeoWithin box' => [
            'expectedOperator' => [
                'geoWithin' => (object) [
                    'path' => ['location1', 'location2'],
                    'box' => (object) [
                        'bottomLeft' => ['coordinates' => [-12.345, -23.456], 'type' => 'Point'],
                        'topRight' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoWithin('location1', 'location2')
                    ->box(new Point([-12.345, -23.456]), new Point([12.345, 23.456]));
            },
        ];

        yield 'GeoWithin circle' => [
            'expectedOperator' => [
                'geoWithin' => (object) [
                    'path' => ['location'],
                    'circle' => (object) [
                        'center' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                        'radius' => 3.14,
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoWithin()
                    ->path('location')
                    ->circle(new Point([12.345, 23.456]), 3.14);
            },
        ];

        yield 'GeoWithin geometry' => [
            'expectedOperator' => [
                'geoWithin' => (object) [
                    'path' => ['location'],
                    'geometry' => [
                        'coordinates' => [
                            [[0, 0], [0, 4], [4, 4], [4, 0], [0, 0]],
                            [[1, 1], [1, 3], [3, 3], [3, 1], [1, 1]],
                        ],
                        'type' => 'Polygon',
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoWithin()
                    ->path('location')
                    ->geometry(new Polygon([
                        [[0, 0], [0, 4], [4, 4], [4, 0], [0, 0]],
                        [[1, 1], [1, 3], [3, 3], [3, 1], [1, 1]],
                    ]));
            },
        ];

        yield 'GeoWithin with boost score' => [
            'expectedOperator' => [
                'geoWithin' => (object) [
                    'path' => ['location'],
                    'circle' => (object) [
                        'center' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                        'radius' => 3.14,
                    ],
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoWithin()
                    ->path('location')
                    ->circle(new Point([12.345, 23.456]), 3.14)
                    ->boostScore(1.5);
            },
        ];

        yield 'GeoWithin with constant score' => [
            'expectedOperator' => [
                'geoWithin' => (object) [
                    'path' => ['location'],
                    'circle' => (object) [
                        'center' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                        'radius' => 3.14,
                    ],
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->geoWithin()
                    ->path('location')
                    ->circle(new Point([12.345, 23.456]), 3.14)
                    ->constantScore(1.5);
            },
        ];
    }

    public static function provideMoreLikeThisBuilders(): Generator
    {
        yield 'MoreLikeThis with single like' => [
            'expectedOperator' => [
                'moreLikeThis' => (object) ['like' => [['title' => 'The Godfather']]],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->moreLikeThis(['title' => 'The Godfather']);
            },
        ];

        yield 'MoreLikeThis with multiple documents' => [
            'expectedOperator' => [
                'moreLikeThis' => (object) [
                    'like' => [
                        ['title' => 'The Godfather'],
                        ['title' => 'The Green Mile'],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->moreLikeThis(['title' => 'The Godfather'], ['title' => 'The Green Mile']);
            },
        ];
    }

    public static function provideNearBuilders(): Generator
    {
        yield 'Near with number' => [
            'expectedOperator' => [
                'near' => (object) [
                    'origin' => 5,
                    'pivot' => 3,
                    'path' => ['value1', 'value2'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->near(5, 3, 'value1', 'value2');
            },
        ];

        $date = new UTCDateTime();

        yield 'Near with date' => [
            'expectedOperator' => [
                'near' => (object) [
                    'origin' => $date,
                    'pivot' => 2,
                    'path' => ['createdAt'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) use ($date) {
                return $stage->near()
                    ->path('createdAt')
                    ->origin($date)
                    ->pivot(2);
            },
        ];

        yield 'Near with point' => [
            'expectedOperator' => [
                'near' => (object) [
                    'origin' => ['coordinates' => [12.345, 23.456], 'type' => 'Point'],
                    'pivot' => 2,
                    'path' => ['createdAt'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->near()
                    ->path('createdAt')
                    ->origin(new Point([12.345, 23.456]))
                    ->pivot(2);
            },
        ];
    }

    public static function providePhraseBuilders(): Generator
    {
        yield 'Phrase required only' => [
            'expectedOperator' => [
                'phrase' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->phrase()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content');
            },
        ];

        yield 'Phrase with slop' => [
            'expectedOperator' => [
                'phrase' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['content'],
                    'slop' => 3,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->phrase()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->slop(3);
            },
        ];

        yield 'Phrase with boost score' => [
            'expectedOperator' => [
                'phrase' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['content'],
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->phrase()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->boostScore(1.5);
            },
        ];

        yield 'Phrase with constant score' => [
            'expectedOperator' => [
                'phrase' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['content'],
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->phrase()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->constantScore(1.5);
            },
        ];
    }

    public static function provideQueryStringBuilders(): Generator
    {
        yield 'QueryString required only' => [
            'expectedOperator' => [
                'queryString' => (object) [
                    'query' => 'MongoDB Aggregation Pipeline',
                    'defaultPath' => 'content',
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->queryString('MongoDB Aggregation Pipeline', 'content');
            },
        ];

        yield 'QueryString with boost score' => [
            'expectedOperator' => [
                'queryString' => (object) [
                    'query' => 'content:pipeline OR title:pipeline',
                    'defaultPath' => 'content',
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->queryString()
                    ->query('content:pipeline OR title:pipeline')
                    ->defaultPath('content')
                    ->boostScore(1.5);
            },
        ];

        yield 'QueryString with constant score' => [
            'expectedOperator' => [
                'queryString' => (object) [
                    'query' => 'content:pipeline OR title:pipeline',
                    'defaultPath' => 'content',
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->queryString()
                    ->query('content:pipeline OR title:pipeline')
                    ->defaultPath('content')
                    ->constantScore(1.5);
            },
        ];
    }

    public static function provideRangeBuilders(): Generator
    {
        yield 'Range gt only' => [
            'expectedOperator' => [
                'range' => (object) [
                    'path' => ['field1', 'field2'],
                    'gt' => 5,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->range()
                    ->path('field1', 'field2')
                    ->gt(5);
            },
        ];

        yield 'Range gte only' => [
            'expectedOperator' => [
                'range' => (object) [
                    'path' => ['field1', 'field2'],
                    'gte' => 5,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->range()
                    ->path('field1', 'field2')
                    ->gte(5);
            },
        ];

        yield 'Range lt only' => [
            'expectedOperator' => [
                'range' => (object) [
                    'path' => ['field1', 'field2'],
                    'lt' => 5,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->range()
                    ->path('field1', 'field2')
                    ->lt(5);
            },
        ];

        yield 'Range lte only' => [
            'expectedOperator' => [
                'range' => (object) [
                    'path' => ['field1', 'field2'],
                    'lte' => 5,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->range()
                    ->path('field1', 'field2')
                    ->lte(5);
            },
        ];

        yield 'Range both bounds' => [
            'expectedOperator' => [
                'range' => (object) [
                    'path' => ['field1', 'field2'],
                    'lte' => 10,
                    'gte' => 5,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->range()
                    ->path('field1', 'field2')
                    ->lte(10)
                    ->gte(5);
            },
        ];

        yield 'Range with boost score' => [
            'expectedOperator' => [
                'range' => (object) [
                    'path' => ['field1', 'field2'],
                    'lte' => 10,
                    'gte' => 5,
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->range()
                    ->path('field1', 'field2')
                    ->lte(10)
                    ->gte(5)
                    ->boostScore(1.5);
            },
        ];

        yield 'Range with constant score' => [
            'expectedOperator' => [
                'range' => (object) [
                    'path' => ['field1', 'field2'],
                    'lte' => 10,
                    'gte' => 5,
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->range()
                    ->path('field1', 'field2')
                    ->lte(10)
                    ->gte(5)
                    ->constantScore(1.5);
            },
        ];
    }

    public static function provideRegexBuilders(): Generator
    {
        yield 'Regex required only' => [
            'expectedOperator' => [
                'regex' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->regex()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content');
            },
        ];

        yield 'Regex with allowAnalyzedField true' => [
            'expectedOperator' => [
                'regex' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'allowAnalyzedField' => true,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->regex()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->allowAnalyzedField();
            },
        ];

        yield 'Regex with allowAnalyzedField false' => [
            'expectedOperator' => [
                'regex' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'allowAnalyzedField' => false,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->regex()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->allowAnalyzedField(false);
            },
        ];

        yield 'Regex with boost score' => [
            'expectedOperator' => [
                'regex' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->regex()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->boostScore(1.5);
            },
        ];

        yield 'Regex with constant score' => [
            'expectedOperator' => [
                'regex' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->regex()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->constantScore(1.5);
            },
        ];
    }

    public static function provideTextBuilders(): Generator
    {
        yield 'Text required only' => [
            'expectedOperator' => [
                'text' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->text()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content');
            },
        ];

        yield 'Text with synonyms' => [
            'expectedOperator' => [
                'text' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['content'],
                    'synonyms' => 'mySynonyms',
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->text()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->synonyms('mySynonyms');
            },
        ];

        yield 'Text with boost score' => [
            'expectedOperator' => [
                'text' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['content'],
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->text()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->boostScore(1.5);
            },
        ];

        yield 'Text with constant score' => [
            'expectedOperator' => [
                'text' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['content'],
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->text()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->constantScore(1.5);
            },
        ];

        yield 'Text with fuzzy search' => [
            'expectedOperator' => [
                'text' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['content'],
                    'fuzzy' => (object) [
                        'maxEdits' => 1,
                        'prefixLength' => 2,
                        'maxExpansions' => 3,
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->text()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('content')
                    ->fuzzy(1, 2, 3);
            },
        ];
    }

    public static function provideWildcardBuilders(): Generator
    {
        yield 'Wildcard required only' => [
            'expectedOperator' => [
                'wildcard' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->wildcard()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content');
            },
        ];

        yield 'Wildcard with allowAnalyzedField true' => [
            'expectedOperator' => [
                'wildcard' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'allowAnalyzedField' => true,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->wildcard()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->allowAnalyzedField();
            },
        ];

        yield 'Wildcard with allowAnalyzedField false' => [
            'expectedOperator' => [
                'wildcard' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'allowAnalyzedField' => false,
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->wildcard()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->allowAnalyzedField(false);
            },
        ];

        yield 'Wildcard with boost score' => [
            'expectedOperator' => [
                'wildcard' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'score' => (object) [
                        'boost' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->wildcard()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->boostScore(1.5);
            },
        ];

        yield 'Wildcard with constant score' => [
            'expectedOperator' => [
                'wildcard' => (object) [
                    'query' => ['MongoDB', 'Aggregation', 'Pipeline'],
                    'path' => ['title', 'content'],
                    'score' => (object) [
                        'constant' => (object) ['value' => 1.5],
                    ],
                ],
            ],
            /** @param Search|CompoundSearchOperatorInterface $stage */
            'createOperator' => static function ($stage) {
                return $stage->wildcard()
                    ->query('MongoDB', 'Aggregation', 'Pipeline')
                    ->path('title', 'content')
                    ->constantScore(1.5);
            },
        ];
    }

    /**
     * @dataProvider provideAutocompleteBuilders
     * @dataProvider provideCompoundBuilders
     * @dataProvider provideEmbeddedDocumentBuilders
     * @dataProvider provideEmbeddedDocumentCompoundBuilders
     * @dataProvider provideEqualsBuilders
     * @dataProvider provideExistsBuilders
     * @dataProvider provideGeoShapeBuilders
     * @dataProvider provideGeoWithinBuilders
     * @dataProvider provideMoreLikeThisBuilders
     * @dataProvider provideNearBuilders
     * @dataProvider providePhraseBuilders
     * @dataProvider provideQueryStringBuilders
     * @dataProvider provideRangeBuilders
     * @dataProvider provideRegexBuilders
     * @dataProvider provideTextBuilders
     * @dataProvider provideWildcardBuilders
     */
    public function testSearchOperators(array $expectedOperator, Closure $createOperator): void
    {
        $baseExpected = [
            'index' => 'my_search_index',
            'highlight' => (object) [
                'path' => 'content',
                'maxCharsToExamine' => 2,
                'maxNumPassages' => 3,
            ],
            'count' => (object) [
                'type' => 'lowerBound',
                'threshold' => 1000,
            ],
            'returnStoredSource' => true,
        ];

        $searchStage = new Search($this->getTestAggregationBuilder());
        $searchStage
            ->index('my_search_index');

        $result = $createOperator($searchStage);

        self::logicalOr(
            new IsInstanceOf(AbstractSearchOperator::class),
            new IsInstanceOf(Search::class),
        );

        $result
            ->highlight('content', 2, 3)
            ->countDocuments('lowerBound', 1000)
            ->returnStoredSource();

        self::assertEquals(
            ['$search' => (object) array_merge($baseExpected, $expectedOperator)],
            $searchStage->getExpression(),
        );
    }

    /**
     * @dataProvider provideAutocompleteBuilders
     * @dataProvider provideEmbeddedDocumentBuilders
     * @dataProvider provideEqualsBuilders
     * @dataProvider provideExistsBuilders
     * @dataProvider provideGeoShapeBuilders
     * @dataProvider provideGeoWithinBuilders
     * @dataProvider provideMoreLikeThisBuilders
     * @dataProvider provideNearBuilders
     * @dataProvider providePhraseBuilders
     * @dataProvider provideQueryStringBuilders
     * @dataProvider provideRangeBuilders
     * @dataProvider provideRegexBuilders
     * @dataProvider provideTextBuilders
     * @dataProvider provideWildcardBuilders
     */
    public function testSearchCompoundOperators(array $expectedOperator, Closure $createOperator): void
    {
        $searchStage = new Search($this->getTestAggregationBuilder());
        $compound    = $searchStage
            ->index('my_search_index')
            ->compound();

        $compound = $createOperator($compound->must());
        $compound = $createOperator($compound->mustNot());
        $compound = $createOperator($compound->should(2));
        $compound = $createOperator($compound->filter());

        self::assertInstanceOf(CompoundSearchOperatorInterface::class, $compound);

        $keys = ['must', 'mustNot', 'should', 'filter'];

        $expected = (object) [
            'index' => 'my_search_index',
            'compound' => (object) array_combine(
                $keys,
                array_map(
                    static fn (string $value): array => [(object) $expectedOperator],
                    $keys,
                ),
            ),
        ];

        $expected->compound->minimumShouldMatch = 2;

        self::assertEquals(
            ['$search' => $expected],
            $searchStage->getExpression(),
        );
    }

    /**
     * @dataProvider provideAutocompleteBuilders
     * @dataProvider provideCompoundBuilders
     * @dataProvider provideEqualsBuilders
     * @dataProvider provideExistsBuilders
     * @dataProvider provideGeoShapeBuilders
     * @dataProvider provideGeoWithinBuilders
     * @dataProvider provideMoreLikeThisBuilders
     * @dataProvider provideNearBuilders
     * @dataProvider providePhraseBuilders
     * @dataProvider provideQueryStringBuilders
     * @dataProvider provideRangeBuilders
     * @dataProvider provideRegexBuilders
     * @dataProvider provideTextBuilders
     * @dataProvider provideWildcardBuilders
     */
    public function testSearchEmbeddedDocumentOperators(array $expectedOperator, Closure $createOperator): void
    {
        $searchStage = new Search($this->getTestAggregationBuilder());
        $embedded    = $searchStage
            ->index('my_search_index')
            ->embeddedDocument('foo');

        $createOperator($embedded);

        $expected = (object) [
            'index' => 'my_search_index',
            'embeddedDocument' => (object) [
                'path' => 'foo',
                'operator' => (object) $expectedOperator,
            ],
        ];

        self::assertEquals(
            ['$search' => $expected],
            $searchStage->getExpression(),
        );
    }
}
