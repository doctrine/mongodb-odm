<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Documents\GraphLookup\Airport;
use Documents\GraphLookup\Employee;
use Documents\GraphLookup\ReportingHierarchy;
use Documents\GraphLookup\Traveller;
use Documents\Sharded\ShardedOne;
use Documents\User;

class GraphLookupTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->requireMongoDB34('$graphLookup tests require at least MongoDB 3.4.0');
    }

    public function provideEmployeeAggregations()
    {
        return [
            'owningSide' => [
                'addGraphLookupStage' => function (Builder $builder) {
                    $builder->graphLookup('reportsTo')
                        ->alias('reportingHierarchy');
                },
                'expectedFields' => [
                    'startWith' => '$reportsTo.id',
                    'connectFromField' => 'reportsTo.id',
                    'connectToField' => '_id',
                ]
            ],
            'owningSideId' => [
                'addGraphLookupStage' => function (Builder $builder) {
                    $builder->graphLookup('reportsToId')
                        ->alias('reportingHierarchy');
                },
                'expectedFields' => [
                    'startWith' => '$reportsToId',
                    'connectFromField' => 'reportsToId',
                    'connectToField' => '_id',
                ]
            ],
            'inverseSide' => [
                'addGraphLookupStage' => function (Builder $builder) {
                    $builder->graphLookup('reportingEmployees')
                        ->alias('reportingHierarchy');
                },
                'expectedFields' => [
                    'startWith' => '$reportsTo.id',
                    'connectFromField' => 'reportsTo.id',
                    'connectToField' => '_id',
                ]
            ],
        ];
    }

    /**
     * @dataProvider provideEmployeeAggregations
     */
    public function testGraphLookupWithEmployees(\Closure $addGraphLookupStage, array $expectedFields)
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
                ], $expectedFields)
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(6, $result);
        foreach ($result as $reportingHierarchy) {
            $this->assertInstanceOf(ReportingHierarchy::class, $reportingHierarchy);
            if ($reportingHierarchy->name === 'Dev') {
                continue;
            }

            $this->assertGreaterThan(0, count($reportingHierarchy->reportingHierarchy));
        }
    }

    public function provideTravellerAggregations()
    {
        return [
            'owningSide' => [
                'addGraphLookupStage' => function (Builder $builder) {
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
                ]
            ],
            'owningSideId' => [
                'addGraphLookupStage' => function (Builder $builder) {
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
                ]
            ],
        ];
    }

    /**
     * @dataProvider provideTravellerAggregations
     */
    public function testGraphLookupWithTraveller(\Closure $addGraphLookupStage, array $expectedFields)
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
                ], $expectedFields)
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());

        $result = $builder->execute()->toArray();

        $this->assertCount(3, $result);
    }

    public function testGraphLookupToShardedCollectionThrowsException()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $this->expectException(MappingException::class);
        $builder
            ->graphLookup(ShardedOne::class);
    }

    public function testGraphLookupWithUnmappedFields()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $builder
            ->graphLookup('someCollection')
                ->startWith('$someExpression')
                ->connectFromField('selfReference')
                ->connectToField('target')
                ->alias('targets')
        ;

        $expectedPipeline = [
            [
                '$graphLookup' => [
                    'from' => 'someCollection',
                    'startWith' => '$someExpression',
                    'connectFromField' => 'selfReference',
                    'connectToField' => 'target',
                    'as' => 'targets',
                    'restrictSearchWithMatch' => (object) [],
                ]
            ]
        ];

        $this->assertEquals($expectedPipeline, $builder->getPipeline());
    }

    public function testGraphLookupWithconnectFromFieldToDifferentTargetClass()
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Reference must target the document itself.');
        $builder
            ->graphLookup('accountSimple')
                ->connectFromField('user')
                ->alias('targets')
        ;
    }

    private function insertEmployeeTestData()
    {
        $dev = new Employee('Dev');
        $eliot = new Employee('Eliot', $dev);
        $ron = new Employee('Ron', $eliot);
        $andrew = new Employee('Andrew', $eliot);
        $asya = new Employee('Asya', $ron);
        $dan = new Employee('Dan', $andrew);

        $this->dm->persist($asya);
        $this->dm->persist($dan);
        $this->dm->flush();
    }

    private function insertTravellerTestData()
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

        $dev = new Traveller('Dev', $jfk);
        $eliot = new Traveller('Eliot', $jfk);
        $jeff = new Traveller('Jeff', $bos);

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
