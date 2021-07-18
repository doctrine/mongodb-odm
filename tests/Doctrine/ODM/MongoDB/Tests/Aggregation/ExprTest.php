<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use LogicException;

class ExprTest extends BaseTest
{
    use AggregationOperatorsProviderTrait;

    /**
     * @dataProvider provideAllOperators
     */
    public function testGenericOperator($expected, $operator, $args): void
    {
        $expr = $this->createExpr();
        $args = $this->resolveArgs($args);

        $this->assertSame($expr, $expr->$operator(...$args));
        $this->assertSame($expected, $expr->getExpression());
    }

    /**
     * @dataProvider provideAllOperators
     */
    public function testGenericOperatorWithField($expected, $operator, $args): void
    {
        $expr = $this->createExpr();
        $args = $this->resolveArgs($args);

        $this->assertSame($expr, $expr->field('foo')->$operator(...$args));
        $this->assertSame(['foo' => $expected], $expr->getExpression());
    }

    public function testExpr(): void
    {
        $expr = $this->createExpr();

        $newExpr = $expr->expr();
        $this->assertInstanceOf(Expr::class, $newExpr);
        $this->assertNotSame($newExpr, $expr);
    }

    public function testExpression(): void
    {
        $nestedExpr = $this->createExpr();
        $nestedExpr
            ->field('dayOfMonth')
            ->dayOfMonth('$dateField')
            ->field('dayOfWeek')
            ->dayOfWeek('$dateField');

        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('nested')->expression($nestedExpr));
        $this->assertSame(
            [
                'nested' => [
                    'dayOfMonth' => ['$dayOfMonth' => '$dateField'],
                    'dayOfWeek' => ['$dayOfWeek' => '$dateField'],
                ],
            ],
            $expr->getExpression()
        );
    }

    public function testExpressionWithoutField(): void
    {
        $nestedExpr = $this->createExpr();
        $nestedExpr
            ->field('dayOfMonth')
            ->dayOfMonth('$dateField')
            ->field('dayOfWeek')
            ->dayOfWeek('$dateField');

        $expr = $this->createExpr();

        $this->expectException(LogicException::class);
        $expr->expression($nestedExpr);
    }

    public function testSwitch(): void
    {
        $expr = $this->createExpr();

        $expr->switch()
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
            $expr->getExpression()
        );
    }

    public function testCallingCaseWithoutSwitchThrowsException(): void
    {
        $expr = $this->createExpr();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::case requires a valid switch statement (call switch() first).');

        $expr->case('$field');
    }

    public function testCallingThenWithoutCaseThrowsException(): void
    {
        $expr = $this->createExpr();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::then requires a valid case statement (call case() first).');

        $expr->then('$field');
    }

    public function testCallingThenWithoutCaseAfterSuccessfulCaseThrowsException(): void
    {
        $expr = $this->createExpr();

        $expr->switch()
            ->case('$field')
            ->then('$field');

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::then requires a valid case statement (call case() first).');

        $expr->then('$field');
    }

    public function testCallingDefaultWithoutSwitchThrowsException(): void
    {
        $expr = $this->createExpr();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage(Expr::class . '::default requires a valid switch statement (call switch() first).');

        $expr->default('$field');
    }
}
