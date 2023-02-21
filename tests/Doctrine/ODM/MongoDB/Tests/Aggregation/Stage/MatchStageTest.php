<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use DateTime;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use GeoJson\Geometry\Geometry;
use MongoDB\BSON\UTCDateTime;
use PHPUnit\Framework\MockObject\MockObject;

class MatchStageTest extends BaseTest
{
    use AggregationTestTrait;

    public function testMatchStage(): void
    {
        $matchStage = new MatchStage($this->getTestAggregationBuilder());
        $matchStage
            ->field('someField')
            ->equals('someValue');

        self::assertSame(['$match' => ['someField' => 'someValue']], $matchStage->getExpression());
    }

    public function testMatchFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->match()
            ->field('someField')
            ->equals('someValue');

        self::assertSame([['$match' => ['someField' => 'someValue']]], $builder->getPipeline());
    }

    /** @dataProvider provideProxiedExprMethods */
    public function testProxiedExprMethods(string $method, array $args = []): void
    {
        $expr = $this->getMockQueryExpr();
        $expr
            ->expects($this->once())
            ->method($method)
            ->with(...$args);

        $stage = new class ($this->getTestAggregationBuilder()) extends MatchStage {
            public function setQuery(Expr $query): void
            {
                $this->query = $query;
            }
        };
        $stage->setQuery($expr);

        self::assertSame($stage, $stage->$method(...$args));
    }

    public function provideProxiedExprMethods(): array
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

    public function testTypeConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(User::class);

        $date      = new DateTime();
        $mongoDate = new UTCDateTime((int) $date->format('Uv'));
        $stage     = $builder
            ->match()
                ->field('createdAt')
                ->lte($date);

        self::assertEquals(
            [
                '$match' => [
                    'createdAt' => ['$lte' => $mongoDate],
                ],
            ],
            $stage->getExpression(),
        );
    }

    /** @return MockObject&Geometry */
    private function getMockGeometry()
    {
        return $this->createMock(Geometry::class);
    }
}
