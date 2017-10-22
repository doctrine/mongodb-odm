<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Documents\CmsComment;

class SortByCountTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFieldNameConversion()
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);
        $builder->sortByCount('$authorIp');

        $this->assertEquals(
            [['$sortByCount' => '$ip']],
            $builder->getPipeline()
        );
    }
}
