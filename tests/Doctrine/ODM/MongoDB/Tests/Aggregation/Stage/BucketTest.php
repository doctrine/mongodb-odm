<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Documents\CmsComment;

class BucketAutoTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFieldNameConversion()
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);

        $builder->bucket()
            ->groupBy('$authorIp')
            ->boundaries(1, 2, 3)
            ->defaultBucket(0)
            ->output()
                ->field('averageValue')
                ->avg('$value');

        $this->assertEquals(
            [['$bucket' => [
                'groupBy' => '$ip',
                'boundaries' => [1, 2, 3],
                'default' => 0,
                'output' => ['averageValue' => ['$avg' => '$value']]
            ]]],
            $builder->getPipeline()
        );
    }
}
