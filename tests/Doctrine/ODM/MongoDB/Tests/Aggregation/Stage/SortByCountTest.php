<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;

class SortByCountTest extends BaseTest
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
