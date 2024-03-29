<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\UnsetStage;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;

class UnsetTest extends BaseTestCase
{
    use AggregationTestTrait;

    public function testStage(): void
    {
        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister(User::class);
        $unsetStage        = new UnsetStage($this->getTestAggregationBuilder(), $documentPersister, 'id', 'foo', 'bar');

        self::assertSame(['$unset' => ['_id', 'foo', 'bar']], $unsetStage->getExpression());
    }

    public function testFromBuilder(): void
    {
        $builder = $this->getTestAggregationBuilder();
        $builder->unset('id', 'foo', 'bar');

        self::assertSame([['$unset' => ['_id', 'foo', 'bar']]], $builder->getPipeline());
    }
}
