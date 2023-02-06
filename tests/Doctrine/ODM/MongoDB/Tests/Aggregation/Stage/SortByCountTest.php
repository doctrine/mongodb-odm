<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\CmsComment;

class SortByCountTest extends BaseTest
{
    public function testFieldNameConversion(): void
    {
        $builder = $this->dm->createAggregationBuilder(CmsComment::class);
        $builder->sortByCount('$authorIp');

        self::assertEquals(
            [['$sortByCount' => '$ip']],
            $builder->getPipeline(),
        );
    }
}
