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

    public function testFilterCriteriaShouldOverridePreparedQuery()
    {
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $persister = $this->uow->getDocumentPersister('Documents\User');

        $criteria = $persister->addFilterToPreparedQuery(array('username' => 'Toby'));

        $this->assertEquals('Tim', $criteria['username']);
    }
}
