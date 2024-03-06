<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH1572Test extends BaseTestCase
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

        self::assertInstanceOf(PersistentCollectionInterface::class, $blog->allPosts);
        self::assertFalse($blog->allPosts->isInitialized());
        self::assertCount(4, $blog->allPosts);
        self::assertTrue($blog->allPosts->isInitialized());

        self::assertInstanceOf(PersistentCollectionInterface::class, $blog->latestPosts);
        self::assertFalse($blog->latestPosts->isInitialized());
        self::assertCount(2, $blog->latestPosts);
        self::assertTrue($blog->latestPosts->isInitialized());

        self::assertInstanceOf(PersistentCollectionInterface::class, $blog->latestPostsRepositoryMethod);
        self::assertFalse($blog->latestPostsRepositoryMethod->isInitialized());
        self::assertCount(4, $blog->latestPostsRepositoryMethod);
        self::assertTrue($blog->latestPostsRepositoryMethod->isInitialized());
    }
}

#[ODM\Document]
class GH1572Blog
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Collection<int, GH1572Post>|array<GH1572Post> */
    #[ODM\ReferenceMany(targetDocument: GH1572Post::class, mappedBy: 'blog')]
    public $allPosts = [];

    /** @var Collection<int, GH1572Post>|array<GH1572Post> */
    #[ODM\ReferenceMany(targetDocument: GH1572Post::class, mappedBy: 'blog', sort: ['id' => 'asc'], limit: 2)]
    public $latestPosts = [];

    /** @var Collection<int, GH1572Post>|array<GH1572Post> */
    #[ODM\ReferenceMany(targetDocument: GH1572Post::class, repositoryMethod: 'getPostsForBlog')]
    public $latestPostsRepositoryMethod = [];
}

#[ODM\Document(repositoryClass: GH1572PostRepository::class)]
class GH1572Post
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var GH1572Blog */
    #[ODM\ReferenceOne(targetDocument: GH1572Blog::class)]
    public $blog;

    public function __construct(GH1572Blog $blog)
    {
        $this->blog       = $blog;
        $blog->allPosts[] = $this;
    }
}

/** @template-extends DocumentRepository<GH1572Blog> */
class GH1572PostRepository extends DocumentRepository
{
    /** @return Iterator<GH1572Blog> */
    public function getPostsForBlog(GH1572Blog $blog): Iterator
    {
        return $this->createQueryBuilder()
            ->field('blog')
            ->references($blog)
            ->getQuery()
            ->getIterator();
    }
}
