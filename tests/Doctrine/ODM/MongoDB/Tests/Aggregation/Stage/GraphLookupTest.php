<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\GraphLookup;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\GraphLookup\Airport;
use Documents\GraphLookup\Employee;
use Documents\GraphLookup\ReportingHierarchy;
use Documents\GraphLookup\Traveller;
use Documents\User;

use function array_merge;
use function count;

class GraphLookupTest extends BaseTest
{
    use AggregationTestTrait;

    public function testGraphLookupStage(): void
    {
        $graphLookupStage = new GraphLookup($this->getTestAggregationBuilder(), 'employees', $this->dm, new ClassMetadata(User::class));
        $graphLookupStage
            ->startWith('$reportsTo')
            ->connectFromField('reportsTo')
            ->connectToField('name')
            ->alias('reportingHierarchy');

        self::assertEquals(
            [
                '$graphLookup' => [
                    'from' => 'employees',
                    'startWith' => '$reportsTo',
                    'connectFromField' => 'reportsTo',
                    'connectToField' => 'name',
                    'as' => 'reportingHierarchy',
                    'restrictSearchWithMatch' => (object) [],
                ],
            ],
            $graphLookupStage->getExpression(),
        );
    }

    public function testGraphLookupFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->graphLookup('employees')
            ->startWith('$reportsTo')
            ->connectFromField('reportsTo')
            ->connectToField('name')
            ->alias('reportingHierarchy');

        self::assertEquals(
            [
                [
                    '$graphLookup' => [
                        'from' => 'employees',
                        'startWith' => '$reportsTo',
                        'connectFromField' => 'reportsTo',
                        'connectToField' => 'name',
                        'as' => 'reportingHierarchy',
                        'restrictSearchWithMatch' => (object) [],
                    ],
                ],
            ],
            $builder->getPipeline(),
        );
    }

    public function testGraphLookupWithMatch(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->graphLookup('employees')
            ->startWith('$reportsTo')
            ->restrictSearchWithMatch()
            ->field('hobbies')
            ->equals('golf')
            ->connectFromField('reportsTo')
            ->connectToField('name')
            ->alias('reportingHierarchy')
            ->maxDepth(1)
            ->depthField('depth');

        self::assertSame(
            [
                [
                    '$graphLookup' => [
                        'from' => 'employees',
                        'startWith' => '$reportsTo',
                        'connectFromField' => 'reportsTo',
                        'connectToField' => 'name',
                        'as' => 'reportingHierarchy',
                        'restrictSearchWithMatch' => ['hobbies' => 'golf'],
                        'maxDepth' => 1,
                        'depthField' => 'depth',
                    ],
                ],
            ],
            $builder->getPipeline(),
        );
    }

    public function provideEmployeeAggregations(): array
    {
        return [
            'owningSide' => [
                'addGraphLookupStage' => static function (Builder $builder) {
                    $builder->graphLookup('reportsTo')
                        ->alias('reportingHierarchy');
                },
                'expectedFields' => [
                    'startWith' => '$reportsTo.id',
                    'connectFromField' => 'reportsTo.id',
                    'connectToField' => '_id',
                ],
            ],
            'owningSideId' => [
                'addGraphLookupStage' => static function (Builder $builder) {
                    $builder->graphLookup('reportsToId')
                        ->alias('reportingHierarchy');
                },
                'expectedFields' => [
                    'startWith' => '$reportsToId',
                    'connectFromField' => 'reportsToId',
                    'connectToField' => '_id',
                ],
            ],
            'inverseSide' => [
                'addGraphLookupStage' => static function (Builder $builder) {
                    $builder->graphLookup('reportingEmployees')
                        ->alias('reportingHierarchy');
                },
                'expectedFields' => [
                    'startWith' => '$reportsTo.id',
                    'connectFromField' => 'reportsTo.id',
                    'connectToField' => '_id',
                ],
            ],
        ];
    }

    /**
     * @param Closure(Builder): GraphLookup $addGraphLookupStage
     * @param array<string, string>         $expectedFields
     *
     * @dataProvider provideEmployeeAggregations
     */
    public function testGraphLookupWithEmployees(Closure $addGraphLookupStage, array $expectedFields): void
    {
        $this->insertEmployeeTestData();

        $builder = $this->dm->createAggregationBuilder(Employee::class);
        $builder->hydrate(ReportingHierarchy::class);
        $addGraphLookupStage($builder);

        $expectedPipeline = [
            [
                '$graphLookup' => array_merge([
                    'from' => 'Employee',
                    'as' => 'reportingHierarchy',
                    'restrictSearchWithMatch' => (object) [],
                ], $expectedFields),
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(6, $result);
        foreach ($result as $reportingHierarchy) {
            self::assertInstanceOf(ReportingHierarchy::class, $reportingHierarchy);
            if ($reportingHierarchy->name === 'Dev') {
                continue;
            }

            self::assertGreaterThan(0, count($reportingHierarchy->reportingHierarchy));
        }
    }

    public function provideTravellerAggregations(): array
    {
        return [
            'owningSide' => [
                'addGraphLookupStage' => static function (Builder $builder) {
                    $builder->graphLookup('nearestAirport')
                        ->connectFromField('connections')
                        ->maxDepth(2)
                        ->depthField('numConnections')
                        ->alias('destinations');
                },
                'expectedFields' => [
                    'startWith' => '$nearestAirport.id',
                    'connectFromField' => 'connections.id',
                    'connectToField' => '_id',
                ],
            ],
            'owningSideId' => [
                'addGraphLookupStage' => static function (Builder $builder) {
                    $builder->graphLookup('nearestAirportId')
                        ->connectFromField('connectionIds')
                        ->maxDepth(2)
                        ->depthField('numConnections')
                        ->alias('destinations');
                },
                'expectedFields' => [
                    'startWith' => '$nearestAirportId',
                    'connectFromField' => 'connectionIds',
                    'connectToField' => '_id',
                ],
            ],
        ];
    }

    /**
     * @param Closure(Builder): GraphLookup $addGraphLookupStage
     * @param array<string, string>         $expectedFields
     *
     * @dataProvider provideTravellerAggregations
     */
    public function testGraphLookupWithTraveller(Closure $addGraphLookupStage, array $expectedFields): void
    {
        $this->insertTravellerTestData();

        $builder = $this->dm->createAggregationBuilder(Traveller::class);
        $addGraphLookupStage($builder);

        $expectedPipeline = [
            [
                '$graphLookup' => array_merge([
                    'from' => 'Airport',
                    'as' => 'destinations',
                    'maxDepth' => 2,
                    'depthField' => 'numConnections',
                    'restrictSearchWithMatch' => (object) [],
                ], $expectedFields),
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        self::assertCount(3, $result);
    }

    public function testGraphLookupWithUnmappedFields(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $builder
            ->graphLookup('someCollection')
                ->startWith('$someExpression')
                ->connectFromField('selfReference')
                ->connectToField('target')
                ->alias('targets');

        $expectedPipeline = [
            [
                '$graphLookup' => [
                    'from' => 'someCollection',
                    'startWith' => '$someExpression',
                    'connectFromField' => 'selfReference',
                    'connectToField' => 'target',
                    'as' => 'targets',
                    'restrictSearchWithMatch' => (object) [],
                ],
            ],
        ];

        self::assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testGraphLookupWithconnectFromFieldToDifferentTargetClass(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Reference must target the document itself.');
        $builder
            ->graphLookup('accountSimple')
                ->connectFromField('user')
                ->alias('targets');
    }

    private function insertEmployeeTestData(): void
    {
        $dev    = new Employee('Dev');
        $eliot  = new Employee('Eliot', $dev);
        $ron    = new Employee('Ron', $eliot);
        $andrew = new Employee('Andrew', $eliot);
        $asya   = new Employee('Asya', $ron);
        $dan    = new Employee('Dan', $andrew);

        $this->dm->persist($asya);
        $this->dm->persist($dan);
        $this->dm->flush();
    }

    private function insertTravellerTestData(): void
    {
        $jfk = new Airport('JFK');
        $bos = new Airport('BOS');
        $ord = new Airport('ORD');
        $pwm = new Airport('PWM');
        $lhr = new Airport('LHR');

        $jfk->addConnection($bos);
        $jfk->addConnection($ord);
        $bos->addConnection($pwm);
        $lhr->addConnection($pwm);

        $dev   = new Traveller('Dev', $jfk);
        $eliot = new Traveller('Eliot', $jfk);
        $jeff  = new Traveller('Jeff', $bos);

        $this->dm->persist($dev);
        $this->dm->persist($eliot);
        $this->dm->persist($jeff);
        $this->dm->persist($jfk);
        $this->dm->persist($bos);
        $this->dm->persist($ord);
        $this->dm->persist($pwm);
        $this->dm->persist($lhr);

        $this->dm->flush();
    }
}
