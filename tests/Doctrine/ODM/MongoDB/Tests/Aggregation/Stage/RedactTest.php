<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Redact;
use Doctrine\ODM\MongoDB\Tests\Aggregation\AggregationTestTrait;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class RedactTest extends BaseTest
{
    use AggregationTestTrait;

    public function testRedactStage()
    {
        $builder = $this->getTestAggregationBuilder();

        $redactStage = new Redact($builder);
        $redactStage
            ->cond(
                $builder->expr()->lte('$accessLevel', 3),
                '$$KEEP',
                '$$REDACT'
            );

        $this->assertSame(['$redact' => ['$cond' => ['if' => ['$lte' => ['$accessLevel', 3]], 'then' => '$$KEEP', 'else' => '$$REDACT']]], $redactStage->getExpression());
    }

    public function testRedactFromBuilder()
    {
        $builder = $this->getTestAggregationBuilder();
        $builder
            ->redact()
            ->cond(
                $builder->expr()->lte('$accessLevel', 3),
                '$$KEEP',
                '$$REDACT'
            );

        $this->assertSame([['$redact' => ['$cond' => ['if' => ['$lte' => ['$accessLevel', 3]], 'then' => '$$KEEP', 'else' => '$$REDACT']]]], $builder->getPipeline());
    }
}
