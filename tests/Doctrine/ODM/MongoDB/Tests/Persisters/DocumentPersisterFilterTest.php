<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use DateTime;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\BlogPost;
use Documents\Comment;
use Documents\User;

class DocumentPersisterFilterTest extends BaseTestCase
{
    public function testAddFilterToPreparedQuery(): void
    {
        $persister        = $this->uow->getDocumentPersister(User::class);
        $filterCollection = $this->dm->getFilterCollection();

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', User::class);
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $preparedQuery = ['username' => 'Toby'];

        $expectedCriteria = [
            '$and' => [
                ['username' => 'Toby'],
                ['username' => 'Tim'],
            ],
        ];

        self::assertSame($expectedCriteria, $persister->addFilterToPreparedQuery($preparedQuery));
    }

    public function testFilterCrieriaShouldAndWithMappingCriteriaOwningSide(): void
    {
        $blogPost = new BlogPost('Roger');
        $blogPost->addComment(new Comment('comment by normal user', new DateTime(), false));
        $blogPost->addComment(new Comment('comment by admin', new DateTime(), true));

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $filterCollection = $this->dm->getFilterCollection();

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', Comment::class);
        $testFilter->setParameter('field', 'isByAdmin');
        $testFilter->setParameter('value', false);

        $blogPost = $this->dm->getRepository(BlogPost::class)->find($blogPost->id);

        // Admin comments should be removed by the filter
        self::assertCount(1, $blogPost->comments);

        /* Admin comments should be removed by the filter, and user comments
         * should be removed by the mapping criteria.
         */
        self::assertEmpty($blogPost->adminComments);
    }
}
