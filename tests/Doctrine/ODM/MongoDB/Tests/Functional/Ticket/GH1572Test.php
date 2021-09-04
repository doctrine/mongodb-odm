<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1572Test extends BaseTest
{
    public function testPersistentCollectionCount(): void
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
        $this->assertTrue($blog->allPosts->isInitialized());

        $this->assertInstanceOf(PersistentCollectionInterface::class, $blog->latestPosts);
        $this->assertFalse($blog->latestPosts->isInitialized());
        $this->assertCount(2, $blog->latestPosts);
        $this->assertTrue($blog->latestPosts->isInitialized());

        $this->assertInstanceOf(PersistentCollectionInterface::class, $blog->latestPostsRepositoryMethod);
        $this->assertFalse($blog->latestPostsRepositoryMethod->isInitialized());
        $this->assertCount(4, $blog->latestPostsRepositoryMethod);
        $this->assertTrue($blog->latestPostsRepositoryMethod->isInitialized());
    }
}

/** @ODM\Document */
class GH1572Blog
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceMany(targetDocument=GH1572Post::class, mappedBy="blog")
     *
     * @var Collection<int, GH1572Post>|array<GH1572Post>
     */
    public $allPosts = [];

    /**
     * @ODM\ReferenceMany(targetDocument=GH1572Post::class, mappedBy="blog", sort={"id"="asc"}, limit=2)
     *
     * @var Collection<int, GH1572Post>|array<GH1572Post>
     */
    public $latestPosts = [];

    /**
     * @ODM\ReferenceMany(targetDocument=GH1572Post::class, repositoryMethod="getPostsForBlog")
     *
     * @var Collection<int, GH1572Post>|array<GH1572Post>
     */
    public $latestPostsRepositoryMethod = [];
}

/** @ODM\Document(repositoryClass=GH1572PostRepository::class) */
class GH1572Post
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=GH1572Blog::class)
     *
     * @var GH1572Blog
     */
    public $blog;

    public function __construct(GH1572Blog $blog)
    {
        $this->blog       = $blog;
        $blog->allPosts[] = $this;
    }
}

class GH1572PostRepository extends DocumentRepository
{
    public function getPostsForBlog(GH1572Blog $blog): Iterator
    {
        return $this->createQueryBuilder()
            ->field('blog')
            ->references($blog)
            ->getQuery()
            ->getIterator();
    }
}
