<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1775Test extends BaseTest
{
    public function testProxyInitializationDoesNotLoseData(): void
    {
        $image = new GH1775Image();
        $this->dm->persist($image);

        $blog = new GH1775Blog();
        $this->dm->persist($blog);
        $this->dm->flush();

        $post1 = new GH1775Post([$blog], [$image]);
        $this->dm->persist($post1);

        $post1->addReferences();
        $this->dm->persist($blog);
        $this->dm->flush();

        $post1Id = $post1->id;
        $imageId = $image->id;
        $blogId  = $blog->id;

        // Clear out DM and read from DB afresh
        $this->dm->clear();

        $blog  = $this->dm->find(GH1775Blog::class, $blogId);
        $image = $this->dm->find(GH1775Image::class, $imageId);

        self::assertInstanceOf(GH1775Blog::class, $blog);
        self::assertInstanceOf(GH1775Image::class, $image);
        $post2 = new GH1775Post([$blog], [$image]);
        $this->dm->persist($post2);

        $post2->addReferences();
        $this->dm->persist($blog);
        $this->dm->flush();

        // Clear out DM and read from DB afresh
        $this->dm->clear();

        $post1 = $this->dm->find(GH1775Post::class, $post1Id);
        $blog  = $this->dm->find(GH1775Blog::class, $blogId);

        self::assertCount(1, $post1->getImages());
        self::assertCount(2, $blog->posts);
    }
}

/** @ODM\MappedSuperclass */
class GH1775MetaDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="int")
     *
     * @var int
     */
    public $version = 5;
}

/** @ODM\Document */
class GH1775Image
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    public function __construct()
    {
    }
}

/** @ODM\Document */
class GH1775Blog
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceMany(targetDocument=GH1775Post::class, inversedBy="blogs")
     *
     * @var Collection<int, GH1775Post>|array<GH1775Post>
     */
    public $posts = [];
}

/** @ODM\Document */
class GH1775Post extends GH1775MetaDocument
{
    /**
     * @ODM\ReferenceMany(targetDocument=GH1775Image::class, storeAs=ClassMetadata::REFERENCE_STORE_AS_ID)
     *
     * @var Collection<int, GH1775Image>
     */
    protected $images;

    /**
     * @ODM\ReferenceMany(targetDocument=GH1775Blog::class, mappedBy="posts")
     *
     * @var Collection<int, GH1775Blog>
     */
    protected $blogs;

    /**
     * @param array<GH1775Blog>  $blogs
     * @param array<GH1775Image> $images
     */
    public function __construct(array $blogs, array $images)
    {
        $this->blogs  = new ArrayCollection($blogs);
        $this->images = new ArrayCollection($images);
    }

    public function addReferences(): void
    {
        foreach ($this->blogs as $blog) {
            if ($blog->posts->contains($this)) {
                continue;
            }

            $blog->posts->add($this);
        }
    }

    /** @return Collection<int, GH1775Image> */
    public function getImages()
    {
        return $this->images;
    }
}
