<?php

namespace Doctrine\ODM\MongoDB\Tests\Aggregation;

class BuilderTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testAggregationBuilder()
    {
        $this->insertTestData();

        $builder = $this->dm->createAggregationBuilder('Documents\BlogPost');

        $aggregationResult = $builder
            ->hydrate('Documents\BlogTagAggregation')
            ->unwind('$tags')
            ->group()
                ->field('id')
                ->expression('$tags')
                ->field('numPosts')
                ->sum(1)
            ->sort('numPosts', 'desc')
            ->execute();

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\CommandCursor', $aggregationResult);
        $this->assertCount(2, $aggregationResult);

        $results = $aggregationResult->toArray();
        $this->assertInstanceOf('Documents\BlogTagAggregation', $results[0]);

        $this->assertSame('baseball', $results[0]->tag->name);
        $this->assertSame(3, $results[0]->numPosts);
    }

    private function insertTestData()
    {
        $baseballTag = new \Documents\Tag('baseball');
        $footballTag = new \Documents\Tag('football');

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 1';
        $blogPost->addTag($baseballTag);
        $this->dm->persist($blogPost);

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 2';
        $blogPost->addTag($baseballTag);
        $this->dm->persist($blogPost);

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 3';
        $blogPost->addTag($footballTag);
        $this->dm->persist($blogPost);

        $blogPost = new \Documents\BlogPost();
        $blogPost->name = 'Test 4';
        $blogPost->addTag($baseballTag);
        $blogPost->addTag($footballTag);
        $this->dm->persist($blogPost);

        $this->dm->flush();
        $this->dm->clear();
    }
}
