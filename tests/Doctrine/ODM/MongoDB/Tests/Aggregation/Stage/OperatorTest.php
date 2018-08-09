<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Operator;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationOperatorsProviderTrait;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class OperatorTest extends BaseTest
{
    use AggregationTestTrait, AggregationOperatorsProviderTrait;

    /**
     * @dataProvider provideExpressionOperators
     */
    public function testProxiedExpressionOperators($expected, $operator, $args)
    {
        $stage = $this->getStubStage();
        $args = $this->resolveArgs($args);

        $this->assertSame($stage, $stage->$operator(...$args));
        $this->assertSame($expected, $stage->getExpression());
    }

    public function testExpression()
    {
        $stage = $this->getStubStage();

        $nestedExpr = $this->createExpr();
        $nestedExpr
            ->field('dayOfMonth')
            ->dayOfMonth('$dateField')
            ->field('dayOfWeek')
            ->dayOfWeek('$dateField');

        $this->assertSame($stage, $stage->field('nested')->expression($nestedExpr));
        $this->assertSame(
            [
                'nested' => [
                    'dayOfMonth' => ['$dayOfMonth' => '$dateField'],
                    'dayOfWeek' => ['$dayOfWeek' => '$dateField'],
                ],
            ],
            $stage->getExpression()
        );
    }

    public function testSwitch()
    {
        $stage = $this->getStubStage();

        $stage->switch()
            ->case($this->createExpr()->eq('$numElements', 0))
            ->then('Zero elements given')
            ->case($this->createExpr()->eq('$numElements', 1))
            ->then('One element given')
            ->default($this->createExpr()->concat('$numElements', ' elements given'));

        $this->assertSame(
            [
                '$switch' => [
                    'branches' => [
                        ['case' => ['$eq' => ['$numElements', 0]], 'then' => 'Zero elements given'],
                        ['case' => ['$eq' => ['$numElements', 1]], 'then' => 'One element given'],
                    ],
                    'default' => ['$concat' => ['$numElements', ' elements given']],
                ],
            ],
            $stage->getExpression()
        );
    }

    public function testCallingCaseWithoutSwitchThrowsException()
    {
        $stage = $this->getStubStage();

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::case requires a valid switch statement (call switch() first).');

        $stage->case('$field');
    }

    public function testCallingThenWithoutCaseThrowsException()
    {
        $stage = $this->getStubStage();

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::then requires a valid case statement (call case() first).');

        $stage->then('$field');
    }

    public function testCallingThenWithoutCaseAfterSuccessfulCaseThrowsException()
    {
        $stage = $this->getStubStage();

        $stage->switch()
            ->case('$field')
            ->then('$field');

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::then requires a valid case statement (call case() first).');

        $stage->then('$field');
    }

    public function testCallingDefaultWithoutSwitchThrowsException()
    {
        $stage = $this->getStubStage();

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::default requires a valid switch statement (call switch() first).');

        $stage->default('$field');
    }

    private function getStubStage(): Operator
    {
        return new class($this->getTestAggregationBuilder()) extends Operator {
            public function getExpression(): array
            {
                return $this->expr->getExpression();
            }
        };
    }
}
