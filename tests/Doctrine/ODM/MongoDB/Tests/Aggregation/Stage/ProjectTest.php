<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Project;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function array_combine;
use function array_map;

class ProjectTest extends BaseTest
{
    use AggregationTestTrait;

    public function testProjectStage(): void
    {
        $projectStage = new Project($this->getTestAggregationBuilder());
        $projectStage
            ->excludeFields(['_id'])
            ->includeFields(['$field', '$otherField'])
            ->field('product')
            ->multiply('$field', 5);

        self::assertSame(['$project' => ['_id' => false, '$field' => true, '$otherField' => true, 'product' => ['$multiply' => ['$field', 5]]]], $projectStage->getExpression());
    }

    public function testProjectFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->project()
            ->excludeFields(['_id'])
            ->includeFields(['$field', '$otherField'])
            ->field('product')
            ->multiply('$field', 5);

        self::assertSame([['$project' => ['_id' => false, '$field' => true, '$otherField' => true, 'product' => ['$multiply' => ['$field', 5]]]]], $builder->getPipeline());
    }

    /** @dataProvider provideAccumulators */
    public function testAccumulatorsWithMultipleArguments(string $operator): void
    {
        $projectStage = new Project($this->getTestAggregationBuilder());
        $projectStage
            ->field('something')
            ->$operator('$expression1', '$expression2');

        self::assertSame(['$project' => ['something' => ['$' . $operator => ['$expression1', '$expression2']]]], $projectStage->getExpression());
    }

    public function provideAccumulators(): array
    {
        $operators = ['avg', 'max', 'min', 'stdDevPop', 'stdDevSamp', 'sum'];

        return array_combine($operators, array_map(static fn ($operator) => [$operator], $operators));
    }

    /**
     * @param string[] $args
     *
     * @dataProvider provideProxiedExprMethods
     */
    public function testProxiedExprMethods(string $method, array $args = []): void
    {
        $expr = $this->getMockAggregationExpr();
        $expr
            ->expects($this->once())
            ->method($method)
            ->with(...$args);

        $stage = new class ($this->getTestAggregationBuilder()) extends Project {
            public function setExpr(Expr $expr): void
            {
                $this->expr = $expr;
            }
        };
        $stage->setExpr($expr);

        self::assertSame($stage, $stage->$method(...$args));
    }

    /** @return array<array{string, string[]}> */
    public static function provideProxiedExprMethods(): array
    {
        return [
            'avg()' => ['avg', ['$field']],
            'max()' => ['max', ['$field']],
            'min()' => ['min', ['$field']],
            'stdDevPop()' => ['stdDevPop', ['$field']],
            'stdDevSamp()' => ['stdDevSamp', ['$field']],
            'sum()' => ['sum', ['$field']],
        ];
    }
}
