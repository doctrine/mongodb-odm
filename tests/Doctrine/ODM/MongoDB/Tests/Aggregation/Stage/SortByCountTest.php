<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\CmsComment;

class SortByCountTest extends BaseTestCase
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
