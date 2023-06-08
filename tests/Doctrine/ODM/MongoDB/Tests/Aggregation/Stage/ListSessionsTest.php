<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\ListSessions;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class ListSessionsTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $listSessionsStage = new ListSessions($this->getTestAggregationBuilder());

        self::assertSame(['$listSessions' => []], $listSessionsStage->getExpression());
    }

    public function testStageWillAllUsersConfig(): void
    {
        $listSessionsStage = new ListSessions($this->getTestAggregationBuilder(), ['allUsers' => true]);

        self::assertSame(['$listSessions' => ['allUsers' => true]], $listSessionsStage->getExpression());
    }

    public function testStageWillSpecificUsersConfig(): void
    {
        $listSessionsStage = new ListSessions($this->getTestAggregationBuilder(), ['users' => [['user' => 'admin', 'db' => 'db']]]);

        self::assertSame(['$listSessions' => ['users' => [['user' => 'admin', 'db' => 'db']]]], $listSessionsStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->listSessions();

        self::assertSame([['$listSessions' => []]], $builder->getPipeline());
    }
}
