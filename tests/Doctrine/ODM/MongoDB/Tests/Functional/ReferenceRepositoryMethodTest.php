<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\BlogPost;
use Documents\Comment;
use Documents\User;

use function assert;
use function strtotime;

class ReferenceRepositoryMethodTest extends BaseTest
{
    public function testOneToOne()
    {
        $date1 = new DateTime();
        $date1->setTimestamp(strtotime('-20 seconds'));

        $date2 = new DateTime();
        $date2->setTimestamp(strtotime('-10 seconds'));

        $blogPost = new BlogPost('Test');
        $blogPost->addComment(new Comment('Comment 1', $date1));
        $blogPost->addComment(new Comment('Comment 2', $date2));
        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
            ->getQuery()
            ->getSingleResult();

        assert($blogPost instanceof BlogPost);

        $this->assertEquals('Comment 2', $blogPost->repoComment->getText());
        $this->assertEquals('Comment 1', $blogPost->repoComments[0]->getText());
        $this->assertEquals('Comment 2', $blogPost->repoComments[1]->getText());
    }

    /**
     * Tests Bi-Directional Reference "one to many" with nullable=true flag
     *
     * @url http://docs.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/bidirectional-references.html
     */
    public function testOneToMany()
    {
        $user = new User();
        $user->setUsername('w00ting');
        $this->dm->persist($user);
        $this->dm->flush();

        $post1       = new BlogPost();
        $post1->name = 'post1';
        $post1->setUser($user);

        $post2       = new BlogPost();
        $post2->name = 'post2';
        $post2->setUser($user);

        $post3       = new BlogPost();
        $post3->name = 'post3';
        $post3->setUser($user);

        $this->dm->persist($post1);
        $this->dm->persist($post2);
        $this->dm->persist($post3);
        $this->dm->flush();

        $post1->setUser(null);
        $this->dm->flush();

        $this->assertNull($post1->user);
    }

    public function testSetStrategy()
    {
        $repo = $this->dm->getRepository(BlogPost::class);

        $blogPost = new BlogPost('Test');

        $blogPost->addComment(new Comment('Comment', new DateTime()));
        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
                  ->getQuery()
                  ->getSingleResult();
        assert($blogPost instanceof BlogPost);
        $this->assertEquals('Comment', $blogPost->repoCommentsSet[0]->getText());
    }

    public function testRepositoryMethodWithoutMappedBy()
    {
        $blogPost = new BlogPost('Test');

        $blogPost->addComment(new Comment('Comment', new DateTime()));
        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $blogPost = $this->dm->createQueryBuilder(BlogPost::class)
            ->getQuery()
            ->getSingleResult();
        assert($blogPost instanceof BlogPost);
        $this->assertCount(1, $blogPost->repoCommentsWithoutMappedBy);
        $this->assertEquals('Comment', $blogPost->repoCommentsWithoutMappedBy[0]->getText());
    }
}
