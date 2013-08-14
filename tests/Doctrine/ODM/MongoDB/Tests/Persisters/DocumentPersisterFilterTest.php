<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Tests\BaseTest;

class DocumentPersisterFilterTest extends BaseTest
{
    private $fc;

    public function setUp()
    {
        parent::setUp();
        $this->fc = $this->dm->getFilterCollection();
    }

    public function tearDown()
    {
        unset($this->fc);
        parent::tearDown();
    }

    public function testFilterCriteriaShouldAndWithPreparedQuery()
    {
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $persister = $this->uow->getDocumentPersister('Documents\User');

        $criteria = $persister->addFilterToPreparedQuery(array('username' => 'Toby'));

        $this->assertEquals(['Toby', 'Tim'], $criteria['username']);
    }

    public function testFilterCrieriaShouldAndWithMappingCriteriaOwningSide(){

        //create some data to test against
        $blogPost = new \Documents\BlogPost('Roger');
        $blogPost->addComment(new \Documents\Comment('comment by normal user', new \DateTime(), false));
        $blogPost->addComment(new \Documents\Comment('comment by admin', new \DateTime(), true));

        $dm = $this->dm;
        $dm->persist($blogPost);
        $dm->flush();
        $id = $blogPost->id;
        $dm->clear();

        //enable the filter
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\Comment');
        $testFilter->setParameter('field', 'isByAdmin');
        $testFilter->setParameter('value', false);

        //test the filter
        $blogPost = $dm->getRepository('Documents\BlogPost')->find($id);

        $this->assertCount(1, $blogPost->comments); //the admin comment should be removed by the filter
        $this->assertCount(0, $blogPost->adminComments); //the user comment should be removed by the mapping criteria, and the admin comment by the filter

        $dm->remove($blogPost);
        $dm->flush();
    }
}
