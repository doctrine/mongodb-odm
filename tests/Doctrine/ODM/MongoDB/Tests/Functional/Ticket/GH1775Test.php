<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1775Test extends BaseTest
{
    public function testProxyInitializationDoesNotLoseData()
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
        $blogId = $blog->id;

        // Clear out DM and read from DB afresh
        $this->dm->clear();

        $blog = $this->dm->find(GH1775Blog::class, $blogId);
        $image = $this->dm->find(GH1775Image::class, $imageId);

        $post2 = new GH1775Post([$blog], [$image]);
        $this->dm->persist($post2);

        $post2->addReferences();
        $this->dm->persist($blog);
        $this->dm->flush();

        // Clear out DM and read from DB afresh
        $this->dm->clear();

        $post1 = $this->dm->find(GH1775Post::class, $post1Id);
        $blog = $this->dm->find(GH1775Blog::class, $blogId);

        $this->assertCount(1, $post1->getImages());
        $this->assertCount(2, $blog->posts);
    }
}

/** @ODM\MappedSuperclass */
class GH1775MetaDocument
{
    /** @ODM\Id */
    public $id;

    /**
     * @var int
     *
     * @ODM\Field(type="int")
     */
    public $version = 5;
}

/** @ODM\Document */
class GH1775Image
{
    /** @ODM\Id */
    public $id;

    public function __construct()
    {
    }
}

/** @ODM\Document */
class GH1775Blog
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceMany(targetDocument="GH1775Post", inversedBy="blogs") */
    public $posts = [];
}

/** @ODM\Document */
class GH1775Post extends GH1775MetaDocument
{
    /** @ODM\ReferenceMany(targetDocument="GH1775Image", simple=true) */
    protected $images;

    /** @ODM\ReferenceMany(targetDocument="GH1775Blog", mappedBy="posts") */
    protected $blogs;

    public function __construct(array $blogs, array $images)
    {
        $this->blogs = new ArrayCollection($blogs);
        $this->images = new ArrayCollection($images);
    }

    function addReferences() {
        foreach ($this->blogs as $blog) {
            if (!$blog->posts->contains($this)) {
                $blog->posts->add($this);
            }
        }
    }

    /**
     * @return Collection
     */
    public function getImages()
    {
        return $this->images;
    }
}
