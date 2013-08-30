<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\User;
use Documents\Group;
use Documents\Profile;

class FilterTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->ids = array();

        $groupA = new Group('groupA');
        $groupB = new Group('groupB');

        $profile = new Profile();
        $profile->setFirstname('Timothy');

        $tim = new User();
        $tim->setUsername('Tim');
        $tim->setHits(10);
        $tim->addGroup($groupA);
        $tim->addGroup($groupB);
        $tim->setProfile($profile);
        $this->dm->persist($tim);

        $john = new User();
        $john->setUsername('John');
        $john->setHits(10);
        $this->dm->persist($john);

        $this->dm->flush();
        $this->dm->clear();

        $this->ids['tim'] = $tim->getId();
        $this->ids['john'] = $john->getId();

        $this->fc = $this->dm->getFilterCollection();
    }

    protected function enableUserFilter()
    {
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');
    }

    protected function enableGroupFilter()
    {
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\Group');
        $testFilter->setParameter('field', 'name');
        $testFilter->setParameter('value', 'groupA');
    }

    protected function enableProfileFilter()
    {
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\Profile');
        $testFilter->setParameter('field', 'firstname');
        $testFilter->setParameter('value', 'Something Else');
    }

    public function testRepositoryFind()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithFind());

        $this->enableUserFilter();
        $this->dm->clear();
        $this->assertEquals(array('Tim'), $this->getUsernamesWithFind());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithFind());
    }

    protected function getUsernamesWithFind()
    {
        $repository = $this->dm->getRepository('Documents\User');

        $tim = $repository->find($this->ids['tim']);
        $john = $repository->find($this->ids['john']);

        $usernames = array();

        if(isset($tim)) {
            $usernames[] = $tim->getUsername();
        }
        if(isset($john)) {
            $usernames[] = $john->getUsername();
        }

        sort($usernames);
        return $usernames;
    }

    public function testRepositoryFindBy()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithFindBy());

        $this->enableUserFilter();
        $this->dm->clear();
        $this->assertEquals(array('Tim'), $this->getUsernamesWithFindBy());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithFindBy());
    }

    protected function getUsernamesWithFindBy()
    {
        $all = $this->dm->getRepository('Documents\User')->findBy(array('hits' => 10));

        $usernames = array();
        foreach ($all as $user) {
            $usernames[] = $user->getUsername();
        }
        sort($usernames);
        return $usernames;
    }

    public function testRepositoryFindOneBy()
    {
        $this->assertEquals('John', $this->getJohnsUsernameWithFindOneBy());

        $this->enableUserFilter();
        $this->dm->clear();
        $this->assertEquals(null, $this->getJohnsUsernameWithFindOneBy());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals('John', $this->getJohnsUsernameWithFindOneBy());
    }

    protected function getJohnsUsernameWithFindOneBy()
    {
        $john = $this->dm->getRepository('Documents\User')->findOneBy(array('id' => $this->ids['john']));

        return isset($john) ? $john->getUsername() : null;
    }

    public function testRepositoryFindAll()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithFindAll());

        $this->enableUserFilter();
        $this->dm->clear();
        $this->assertEquals(array('Tim'), $this->getUsernamesWithFindAll());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithFindAll());
    }

    protected function getUsernamesWithFindAll()
    {
        $all = $this->dm->getRepository('Documents\User')->findAll();

        $usernames = array();
        foreach ($all as $user) {
            $usernames[] = $user->getUsername();
        }
        sort($usernames);
        return $usernames;
    }

    public function testReferenceMany()
    {
        $this->assertEquals(array('groupA', 'groupB'), $this->getGroupsByReference());

        $this->enableGroupFilter();
        $this->dm->clear();
        $this->assertEquals(array('groupA'), $this->getGroupsByReference());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals(array('groupA', 'groupB'), $this->getGroupsByReference());
    }

    protected function getGroupsByReference()
    {
        $tim = $this->dm->getRepository('Documents\User')->find($this->ids['tim']);

        $groupnames = array();
        foreach ($tim->getGroups() as $group) {
            try {
                $groupnames[] = $group->getName();
            } catch (\Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
               //Proxy object filtered
            }
        }
        sort($groupnames);
        return $groupnames;
    }

    public function testReferenceOne()
    {
        $this->assertEquals('Timothy', $this->getProfileByReference());

        $this->enableProfileFilter();
        $this->dm->clear();
        $this->assertEquals(null, $this->getProfileByReference());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals('Timothy', $this->getProfileByReference());
    }

    protected function getProfileByReference()
    {
        $tim = $this->dm->getRepository('Documents\User')->find($this->ids['tim']);

        $profile = $tim->getProfile();
        try {
            return $profile->getFirstname();
        } catch (\Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
            //Proxy object filtered
            return null;
        }
    }

    public function testDocumentManagerRef()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithDocumentManager());

        $this->enableUserFilter();
        $this->dm->clear();
        $this->assertEquals(array('Tim'), $this->getUsernamesWithDocumentManager());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithDocumentManager());
    }

    protected function getUsernamesWithDocumentManager()
    {
        $tim = $this->dm->getReference('Documents\User', $this->ids['tim']);
        $john = $this->dm->getReference('Documents\User', $this->ids['john']);

        $usernames = array();

        try {
            $usernames[] = $tim->getUsername();
        } catch (\Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
            //Proxy object filtered
        }

        try {
            $usernames[] = $john->getUsername();
        } catch (\Doctrine\ODM\MongoDB\DocumentNotFoundException $e) {
            //Proxy object filtered
        }

        sort($usernames);
        return $usernames;
    }

    public function testQuery()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithQuery());

        $this->enableUserFilter();
        $this->dm->clear();
        $this->assertEquals(array('Tim'), $this->getUsernamesWithQuery());

        $this->fc->disable('testFilter');
        $this->dm->clear();
        $this->assertEquals(array('John', 'Tim'), $this->getUsernamesWithQuery());
    }

    protected function getUsernamesWithQuery()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User');
        $query = $qb->getQuery();
        $all = $query->execute();

        $usernames = array();
        foreach ($all as $user) {
            $usernames[] = $user->getUsername();
        }
        sort($usernames);
        return $usernames;
    }

    public function testMultipleFiltersOnSameField()
    {
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $this->fc->enable('testFilter2');
        $testFilter2 = $this->fc->getFilter('testFilter2');
        $testFilter2->setParameter('class', 'Documents\User');
        $testFilter2->setParameter('field', 'username');
        $testFilter2->setParameter('value', 'John');

        /* These two filters will merge and create a query that requires the
         * username to equal both "Tim" and "John", which is impossible for a
         * non-array, string field. No results should be returned.
         */
        $this->assertCount(0, $this->getUsernamesWithFindAll());
    }
}
