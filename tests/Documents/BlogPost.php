<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class BlogPost
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, Tag>|array<Tag> */
    #[ODM\ReferenceMany(targetDocument: Tag::class, inversedBy: 'blogPosts', cascade: ['all'])]
    public $tags = [];

    /** @var Collection<int, Comment>|array<Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'parent', cascade: ['all'], prime: ['author'])]
    public $comments = [];

    /** @var Comment|null */
    #[ODM\ReferenceOne(targetDocument: Comment::class, mappedBy: 'parent', sort: ['date' => 'asc'])]
    public $firstComment;

    /** @var Comment|null */
    #[ODM\ReferenceOne(targetDocument: Comment::class, mappedBy: 'parent', sort: ['date' => 'desc'])]
    public $latestComment;

    /** @var Collection<int, Comment>|array<Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'parent', sort: ['date' => 'desc'], limit: 5)]
    public $last5Comments = [];

    /** @var Collection<int, Comment>|array<Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'parent', criteria: ['isByAdmin' => true], sort: ['date' => 'desc'])]
    public $adminComments = [];

    /** @var Comment|null */
    #[ODM\ReferenceOne(targetDocument: Comment::class, mappedBy: 'parent', repositoryMethod: 'findOneComment')]
    public $repoComment;

    /** @var Collection<int, Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'parent', repositoryMethod: 'findManyComments')]
    public $repoComments;

    /** @var Collection<int, Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'parent', repositoryMethod: 'findManyComments', prime: ['author'])]
    public $repoCommentsWithPrimer;

    /** @var Collection<int, Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class, mappedBy: 'parent', strategy: 'set', repositoryMethod: 'findManyComments')]
    public $repoCommentsSet;

    /** @var Collection<int, Comment> */
    #[ODM\ReferenceMany(targetDocument: Comment::class, repositoryMethod: 'findManyComments')]
    public $repoCommentsWithoutMappedBy;

    /** @var User|null */
    #[ODM\ReferenceOne(targetDocument: User::class, inversedBy: 'posts', nullable: true)]
    public $user;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addTag(Tag $tag): void
    {
        $tag->addBlogPost($this); // synchronously updating inverse side
        $this->tags[] = $tag;
    }

    public function addComment(Comment $comment): void
    {
        $comment->parent  = $this;
        $this->comments[] = $comment;
    }

    public function setUser(?User $user = null): void
    {
        $this->user = $user;
    }
}
