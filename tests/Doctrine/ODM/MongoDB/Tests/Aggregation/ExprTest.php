<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

use BadMethodCallException;
use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use LogicException;

class ExprTest extends BaseTest
{
    use AggregationOperatorsProviderTrait;

    /**
     * @param array<string, string>          $expected
     * @param Closure(Expr): mixed[]|mixed[] $args
     *
     * @dataProvider provideAllOperators
     */
    public function testGenericOperator(array $expected, string $operator, $args): void
    {
        $expr = $this->createExpr();
        $args = $this->resolveArgs($args);

        self::assertSame($expr, $expr->$operator(...$args));
        self::assertSame($expected, $expr->getExpression());
    }

    /**
     * @param array<string, string>          $expected
     * @param Closure(Expr): mixed[]|mixed[] $args
     *
     * @dataProvider provideAllOperators
     */
    public function testGenericOperatorWithField(array $expected, string $operator, $args): void
    {
        $expr = $this->createExpr();
        $args = $this->resolveArgs($args);

        self::assertSame($expr, $expr->field('foo')->$operator(...$args));
        self::assertSame(['foo' => $expected], $expr->getExpression());
    }

    public function testExpr(): void
    {
        $expr = $this->createExpr();

        $newExpr = $expr->expr();
        self::assertInstanceOf(Expr::class, $newExpr);
        self::assertNotSame($newExpr, $expr);
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

        self::assertSame($expr, $expr->field('nested')->expression($nestedExpr));
        self::assertSame(
            [
                'nested' => [
                    'dayOfMonth' => ['$dayOfMonth' => '$dateField'],
                    'dayOfWeek' => ['$dayOfWeek' => '$dateField'],
                ],
            ],
            $expr->getExpression(),
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

        self::assertSame(
            [
                '$switch' => [
                    'branches' => [
                        ['case' => ['$eq' => ['$numElements', 0]], 'then' => 'Zero elements given'],
                        ['case' => ['$eq' => ['$numElements', 1]], 'then' => 'One element given'],
                    ],
                    'default' => ['$concat' => ['$numElements', ' elements given']],
                ],
            ],
            $expr->getExpression(),
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
