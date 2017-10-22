<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Documents\CmsComment;

class BucketTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFieldNameConversion()
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);

        $builder->bucketAuto()
            ->groupBy('$authorIp')
            ->buckets(3)
            ->granularity('R10')
            ->output()
                ->field('averageValue')
                ->avg('$value');

        $this->assertEquals(
            [['$bucketAuto' => [
                'groupBy' => '$ip',
                'buckets' => 3,
                'granularity' => 'R10',
                'output' => ['averageValue' => ['$avg' => '$value']]
            ]]],
            $builder->getPipeline()
        );
    }
}
