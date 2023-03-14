<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\UnsetStage;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;

class UnsetTest extends BaseTest
{
    use AggregationTestTrait;

    public function testUnsetStage(): void
    {
        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister(User::class);
        $unsetStage        = new UnsetStage($this->getTestAggregationBuilder(), $documentPersister, 'id', 'foo', 'bar');

        self::assertSame(['$unset' => ['_id', 'foo', 'bar']], $unsetStage->getExpression());
    }

    public function testLimitFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->unset('id', 'foo', 'bar');

        self::assertSame([['$unset' => ['_id', 'foo', 'bar']]], $builder->getPipeline());
    }
}
