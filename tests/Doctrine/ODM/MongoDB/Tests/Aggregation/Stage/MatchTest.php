<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Match;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MatchTest extends BaseTest
{
    use AggregationTestTrait;

    public function testMatchStage()
    {
        $matchStage = new Match($this->getTestAggregationBuilder());
        $matchStage
            ->field('someField')
            ->equals('someValue');

        $this->assertSame(['$match' => ['someField' => 'someValue']], $matchStage->getExpression());
    }

    public function testMatchFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->match()
            ->field('someField')
            ->equals('someValue');

        $this->assertSame([['$match' => ['someField' => 'someValue']]], $builder->getPipeline());
    }

    /**
     * @dataProvider provideProxiedExprMethods
     */
    public function testProxiedExprMethods($method, array $args = [])
    {
        $expr = $this->getMockQueryExpr();
        $expr
            ->expects($this->once())
            ->method($method)
            ->with(...$args);

        $stage = new class($this->getTestAggregationBuilder()) extends Match {
            public function setQuery(Expr $query)
            {
                $this->query = $query;
            }
        };
        $stage->setQuery($expr);

        $this->assertSame($stage, $stage->$method(...$args));
    }

    public function provideProxiedExprMethods()
    {
        return [
            'field()' => ['field', ['fieldName']],
            'equals()' => ['equals', ['value']],
            'in()' => ['in', [['value1', 'value2']]],
            'notIn()' => ['notIn', [['value1', 'value2']]],
            'notEqual()' => ['notEqual', ['value']],
            'gt()' => ['gt', [1]],
            'gte()' => ['gte', [1]],
            'lt()' => ['gt', [1]],
            'lte()' => ['gte', [1]],
            'range()' => ['range', [0, 1]],
            'size()' => ['size', [1]],
            'exists()' => ['exists', [true]],
            'type()' => ['type', [7]],
            'all()' => ['all', [['value1', 'value2']]],
            'mod()' => ['mod', [2, 0]],
            'geoIntersects()' => ['geoIntersects', [$this->getMockGeometry()]],
            'geoWithin()' => ['geoWithin', [$this->getMockGeometry()]],
            'geoWithinBox()' => ['geoWithinBox', [1, 2, 3, 4]],
            'geoWithinCenter()' => ['geoWithinCenter', [1, 2, 3]],
            'geoWithinCenterSphere()' => ['geoWithinCenterSphere', [1, 2, 3]],
            'geoWithinPolygon()' => ['geoWithinPolygon', [[0, 0], [1, 1], [1, 0]]],
            'addAnd() array' => ['addAnd', [[]]],
            'addAnd() Expr' => ['addAnd', [$this->getMockQueryExpr()]],
            'addOr() array' => ['addOr', [[]]],
            'addOr() Expr' => ['addOr', [$this->getMockQueryExpr()]],
            'addNor() array' => ['addNor', [[]]],
            'addNor() Expr' => ['addNor', [$this->getMockQueryExpr()]],
            'not()' => ['not', [$this->getMockQueryExpr()]],
            'language()' => ['language', ['en']],
            'text()' => ['text', ['foo']],
        ];
    }

    public function testTypeConversion()
    {
        $builder = $this->dm->createAggregationBuilder('Documents\User');

        $date = new \DateTime();
        $mongoDate = new \MongoDB\BSON\UTCDateTime((int) $date->format('Uv'));
        $stage = $builder
            ->match()
                ->field('createdAt')
                ->lte($date);

        $this->assertEquals(
            ['$match' => [
                'createdAt' => ['$lte' => $mongoDate]
            ]],
            $stage->getExpression()
        );
    }

    private function getMockGeometry()
    {
        return $this->getMockBuilder('GeoJson\Geometry\Geometry')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
