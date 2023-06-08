<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\ListLocalSessions;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class ListLocalSessionsTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $listLocalSessionsStage = new ListLocalSessions($this->getTestAggregationBuilder());

        self::assertSame(['$listLocalSessions' => []], $listLocalSessionsStage->getExpression());
    }

    public function testStageWillAllUsersConfig(): void
    {
        $listLocalSessionsStage = new ListLocalSessions($this->getTestAggregationBuilder(), ['allUsers' => true]);

        self::assertSame(['$listLocalSessions' => ['allUsers' => true]], $listLocalSessionsStage->getExpression());
    }

    public function testStageWillSpecificUsersConfig(): void
    {
        $listLocalSessionsStage = new ListLocalSessions($this->getTestAggregationBuilder(), ['users' => [['user' => 'admin', 'db' => 'db']]]);

        self::assertSame(['$listLocalSessions' => ['users' => [['user' => 'admin', 'db' => 'db']]]], $listLocalSessionsStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->listLocalSessions();

        self::assertSame([['$listLocalSessions' => []]], $builder->getPipeline());
    }
}
