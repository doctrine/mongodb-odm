<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

class GH597Test extends BaseTest
{
    public function testEmbedManyGetsUnset(): void
    {
        $post = new GH597Post();
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        // default behavior on inserts already leaves out embedded documents
        $expectedDocument = ['_id' => new ObjectId($post->getId())];
        $this->assertPostDocument($expectedDocument, $post);

        // fill documents with comments
        $post           = $this->dm->find(GH597Post::class, $post->getId());
        $post->comments = new ArrayCollection([
            new GH597Comment('Comment 1'),
            new GH597Comment('Comment 2'),
            new GH597Comment('Comment 3'),
        ]);
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        $expectedDocument = [
            '_id' => new ObjectId($post->getId()),
            'comments' => [
                ['comment' => 'Comment 1'],
                ['comment' => 'Comment 2'],
                ['comment' => 'Comment 3'],
            ],
        ];
        $this->assertPostDocument($expectedDocument, $post);

        // trigger update
        $post = $this->dm->find(GH597Post::class, $post->getId());
        self::assertCount(3, $post->getComments());
        $post->comments = null;
        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(GH597Post::class, $post->getId());
        self::assertEmpty($post->getComments());

        // make sure embedded documents got unset
        $expectedDocument = ['_id' => new ObjectId($post->getId())];
        $this->assertPostDocument($expectedDocument, $post);
    }

    public function testReferenceManyGetsUnset(): void
    {
        $post = new GH597Post();
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        // default behavior on inserts already leaves out referenced documents
        $expectedDocument = ['_id' => new ObjectId($post->getId())];
        $this->assertPostDocument($expectedDocument, $post);

        // associate post with many GH597ReferenceMany documents
        $post = $this->dm->find(GH597Post::class, $post->getId());

        $referenceMany1 = new GH597ReferenceMany('one');
        $this->dm->persist($referenceMany1);
        $referenceMany2 = new GH597ReferenceMany('two');
        $this->dm->persist($referenceMany2);

        $post->referenceMany = new ArrayCollection([$referenceMany1, $referenceMany2]);
        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        $expectedDocument = [
            '_id' => new ObjectId($post->getId()),
            'referenceMany' => [
                new ObjectId($referenceMany1->getId()),
                new ObjectId($referenceMany2->getId()),
            ],
        ];
        $this->assertPostDocument($expectedDocument, $post);

        // trigger update
        $post = $this->dm->find(GH597Post::class, $post->getId());
        self::assertCount(2, $post->getReferenceMany());
        $post->referenceMany = null;
        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->find(GH597Post::class, $post->getId());
        self::assertEmpty($post->getReferenceMany());

        // make sure reference-many documents got unset
        $expectedDocument = ['_id' => new ObjectId($post->getId())];
        $this->assertPostDocument($expectedDocument, $post);
    }

    /**
     * Asserts that raw document matches expected document.
     *
     * @param array<string, mixed> $expected
     */
    private function assertPostDocument(array $expected, GH597Post $post): void
    {
        $collection = $this->dm->getDocumentCollection(GH597Post::class);
        $document   = $collection->findOne(['_id' => new ObjectId($post->getId())]);
        self::assertEquals($expected, $document);
    }
}

/** @ODM\Document() */
class GH597Post
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(targetDocument=GH597Comment::class)
     *
     * @var Collection<int, GH597Comment>
     */
    public $comments;

    /**
     * @ODM\ReferenceMany(targetDocument=GH597ReferenceMany::class, storeAs="id")
     *
     * @var Collection<int, GH597ReferenceMany>
     */
    public $referenceMany;

    public function __construct()
    {
        $this->comments      = new ArrayCollection();
        $this->referenceMany = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /** @return Collection<int, GH597Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /** @return Collection<int, GH597ReferenceMany> */
    public function getReferenceMany(): Collection
    {
        return $this->referenceMany;
    }
}

/** @ODM\EmbeddedDocument */
class GH597Comment
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $comment;

    public function __construct(string $comment)
    {
        $this->comment = $comment;
    }
}


/** @ODM\Document */
class GH597ReferenceMany
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
