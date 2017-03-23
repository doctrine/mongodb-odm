<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1572Test extends BaseTest
{
    public function testPersistentCollectionCount()
    {
        $blog = new GH1572Blog();
        $this->dm->persist($blog);

        $this->dm->persist(new GH1572Post($blog));
        $this->dm->persist(new GH1572Post($blog));
        $this->dm->persist(new GH1572Post($blog));
        $this->dm->persist(new GH1572Post($blog));

        $this->dm->flush();
        $this->dm->refresh($blog);

        $this->assertInstanceOf(PersistentCollectionInterface::class, $blog->allPosts);
        $this->assertFalse($blog->allPosts->isInitialized());
        $this->assertCount(4, $blog->allPosts);

        $this->assertInstanceOf(PersistentCollectionInterface::class, $blog->latestPosts);
        $this->assertFalse($blog->latestPosts->isInitialized());
        $this->assertCount(2, $blog->latestPosts);

        $this->assertInstanceOf(PersistentCollectionInterface::class, $blog->latestPostsRepositoryMethod);
        $this->assertFalse($blog->latestPostsRepositoryMethod->isInitialized());
        $this->assertCount(2, $blog->latestPostsRepositoryMethod);
    }
}

/** @ODM\Document */
class GH1572Blog
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(targetDocument="GH1572Post", mappedBy="blog") */
    public $allPosts = [];

    /** @ODM\ReferenceMany(targetDocument="GH1572Post", mappedBy="blog", sort={"id"="asc"}, limit=2) */
    public $latestPosts = [];

    /** @ODM\ReferenceMany(targetDocument="GH1572Post", repositoryMethod="getPostsForBlog", limit=2) */
    public $latestPostsRepositoryMethod = [];
}

/** @ODM\Document(repositoryClass="GH1572PostRepository") */
class GH1572Post
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument="GH1572Blog") */
    public $blog;

    public function __construct(GH1572Blog $blog)
    {
        $this->blog = $blog;
        $blog->allPosts[] = $this;
    }
}

class GH1572PostRepository extends DocumentRepository
{
    public function getPostsForBlog($blog)
    {
        return $this->createQueryBuilder()
            ->field('blog')
            ->references($blog)
            ->getQuery()
            ->getIterator();
    }
}
