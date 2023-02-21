<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function assert;

class GH1232Test extends BaseTest
{
    /** @doesNotPerformAssertions */
    public function testRemoveDoesNotCauseErrors(): void
    {
        $post = new GH1232Post();
        $this->dm->persist($post);
        $this->dm->flush();

        $comment       = new GH1232Comment();
        $comment->post = $post;
        $this->dm->persist($comment);
        $this->dm->flush();

        $this->dm->refresh($post);

        $this->dm->remove($post);
        $this->dm->flush();
    }
}

/** @ODM\Document */
class GH1232Post
{
    public const CLASSNAME = self::class;

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceMany(targetDocument=GH1232Comment::class, mappedBy="post", cascade={"remove"})
     *
     * @var Collection<int, GH1232Comment>
     */
    protected $comments;

    /**
     * @ODM\ReferenceMany(
     *     targetDocument=GH1232Comment::class,
     *     mappedBy="post",
     *     repositoryMethod="getLongComments",
     * )
     *
     * @var Collection<int, GH1232Comment>
     */
    protected $longComments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }
}

/** @ODM\Document(repositoryClass="GH1232CommentRepository") */
class GH1232Comment
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne(targetDocument=GH1232Post::class)
     *
     * @var GH1232Post|null
     */
    public $post;
}

/** @template-extends DocumentRepository<GH1232Comment> */
class GH1232CommentRepository extends DocumentRepository
{
    /** @return Iterator<GH1232Comment> */
    public function getLongComments(GH1232Post $post)
    {
        $comments = $this
            ->createQueryBuilder()
            ->field('post')
            ->references($post)
            ->sort('_id', 'asc')
            ->getQuery()
            ->execute();

        assert($comments instanceof Iterator);

        return $comments;
    }
}
