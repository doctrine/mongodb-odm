<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;
use Documents\Functional\EmbeddedTestLevel0;
use Documents\Functional\EmbeddedTestLevel1;
use Documents\Functional\EmbeddedTestLevel2;

class MODM140Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function testInsertingNestedEmbeddedCollections()
    {
        $category = new Category;
        $category->name = "My Category";

        $post1 = new Post;
        $post1->versions->add(new PostVersion('P1V1'));
        $post1->versions->add(new PostVersion('P1V2'));

        $category->posts->add($post1);

        $this->dm->persist($category);
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->getRepository(__NAMESPACE__ . '\Category')->findOneByName('My Category');
        $post2 = new Post;
        $post2->versions->add(new PostVersion('P2V1'));
        $post2->versions->add(new PostVersion('P2V2'));
        $category->posts->add($post2);

        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->getRepository(__NAMESPACE__ . '\Category')->findOneByName('My Category');
        // Should be: 1 Category, 2 Post, 2 PostVersion in each Post
        $this->assertEquals(2, $category->posts->count());
        $this->assertEquals(2, $category->posts->get(0)->versions->count());
        $this->assertEquals(2, $category->posts->get(1)->versions->count());
    }

    public function testInsertingEmbeddedCollectionWithRefMany()
    {
        $comment = new Comment();

        $post = new Post();
        $post->comments[] = $comment;

        $category = new Category();
        $category->name = "My Category";
        $category->posts->add($post);

        $this->dm->persist($comment);
        $this->dm->persist($post);
        $this->dm->persist($category);
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->getRepository(__NAMESPACE__ . '\Category')->findOneByName('My Category');
        $this->assertEquals(1, $category->posts->count());
        $this->assertEquals(1, $category->posts->get(0)->comments->count());
    }

    public function testAddingAnotherEmbeddedDocument()
    {
        $test = new EmbeddedTestLevel0();
        $test->name = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository('Documents\Functional\EmbeddedTestLevel0')->findOneBy(array('name' => 'test'));
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel0', $test);

        $level1 = new EmbeddedTestLevel1();
        $level1->name = "test level 1 #1";

        $level2 = new EmbeddedTestLevel2();
        $level2->name = "test level 2 #1 in level 1 #1";
        $level1->level2[] = $level2;

        $level2 = new EmbeddedTestLevel2();
        $level2->name = "test level 2 #2 in level 1 #1";
        $level1->level2[] = $level2;

        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository('Documents\Functional\EmbeddedTestLevel0')->findOneBy(array('name' => 'test'));
        $this->assertEquals(1, count($test->level1));
        $this->assertEquals(2, count($test->level1[0]->level2));

        $level1 = new EmbeddedTestLevel1();
        $level1->name = "test level 1 #2";

        $level2 = new EmbeddedTestLevel2();
        $level2->name = "test level 2 #1 in level 1 #2";
        $level1->level2[] = $level2;

        $level2 = new EmbeddedTestLevel2();
        $level2->name = "test level 2 #2 in level 1 #2";
        $level1->level2[] = $level2;

        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository('Documents\Functional\EmbeddedTestLevel0')->findOneBy(array('name' => 'test'));
        $this->assertEquals(2, count($test->level1));
        $this->assertEquals(2, count($test->level1[0]->level2));
        $this->assertEquals(2, count($test->level1[1]->level2));
    }
	
}

/** @ODM\Document */
class Category 
{
	/** @ODM\Id */
	protected $id;
	
	/** @ODM\Field(type="string") */
	public $name;
	
	/** @ODM\EmbedMany(targetDocument="Post") */
	public $posts;
	
	public function __construct()
	{
		$this->posts = new ArrayCollection();
	}
	
}

/** @ODM\EmbeddedDocument */
class Post
{
	/** @ODM\EmbedMany(targetDocument="PostVersion") */
	public $versions;
	
	/** @ODM\ReferenceMany(targetDocument="Comment") */
	public $comments;

	public function __construct()
	{
		$this->versions = new ArrayCollection();
		$this->comments = new ArrayCollection();
	}
	
}

/** @ODM\EmbeddedDocument */
class PostVersion
{
	/** @ODM\Field(type="string") */
	public $name;
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
}

/** @ODM\Document */
class Comment
{
	/** @ODM\Id */
	protected $id;

	/** @ODM\Field(type="string") */
	public $content;
}
