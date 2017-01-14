<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation\Stage;

class MatchTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTypeConversion()
    {
        $builder = $this->dm->createAggregationBuilder('Documents\User');

        $date = new \DateTime();
        $mongoDate = new \MongoDate($date->format('U'), $date->format('u'));
        $stage = $builder
            ->match()
                ->field('createdAt')
                ->lte($date);

        $this->assertEquals(
            ['$match' => [
                'createdAt' => ['$lte' => $mongoDate]
            ]],
            $stage->getExpression()
        );
    }
}
