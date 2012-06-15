<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\User;
use Documents\Group;
use Documents\Profile;

class FilterTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp(){
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

    protected function enableUserFilter(){
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');      
    }
    
    protected function enableGroupFilter(){
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\Group');
        $testFilter->setParameter('field', 'name');
        $testFilter->setParameter('value', 'groupA');      
    }
        
    protected function enableProfileFilter(){
        $this->fc->enable('testFilter');
        $testFilter = $this->fc->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\Profile');
        $testFilter->setParameter('field', 'firstname');
        $testFilter->setParameter('value', 'Something Else');      
    }
    
    public function testRepositoryFind()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getRepositoryFind());
        
        $this->enableUserFilter();               
        $this->assertEquals(array('Tim'), $this->getRepositoryFind());

        $this->fc->disable('testFilter');
        $this->assertEquals(array('John', 'Tim'), $this->getRepositoryFind());
    }
    
    protected function getRepositoryFind(){
        $repository = $this->dm->getRepository('Documents\User');
        
        $tim = $repository->find($this->ids['tim']);
        $john = $repository->find($this->ids['john']);

        $usernames = array();
        
        if(isset($tim)){
            $usernames[] = $tim->getUsername(); 
        }
        if(isset($john)){
            $usernames[] = $john->getUsername(); 
        }
      
        sort($usernames);
        return $usernames;        
    }    
    
    public function testRepositoryFindBy()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getRepositoryFindBy());
        
        $this->enableUserFilter();               
        $this->assertEquals(array('Tim'), $this->getRepositoryFindBy());

        $this->fc->disable('testFilter');
        $this->assertEquals(array('John', 'Tim'), $this->getRepositoryFindBy());
    }
    
    protected function getRepositoryFindBy(){
        $all = $this->dm->getRepository('Documents\User')->findBy(array('hits' => 10));

        $usernames = array();
        foreach($all as $user){
            $usernames[] = $user->getUsername();
        }
        sort($usernames);
        return $usernames;        
    }
    
    public function testRepositoryFindOneBy()
    {
        $this->assertEquals('John', $this->getRepositoryFindOneBy());
        
        $this->enableUserFilter();              
        $this->assertEquals(null, $this->getRepositoryFindOneBy());

        $this->fc->disable('testFilter');
        $this->assertEquals('John', $this->getRepositoryFindOneBy());
    }
    
    protected function getRepositoryFindOneBy(){
        $john = $this->dm->getRepository('Documents\User')->findOneBy(array('id' => $this->ids['john']));

        if(isset($john)){
            return $john->getUsername();
        } else {
            return;
        }
    }
    
    public function testRepositoryFindAll()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getRepositoryFindAll());

        $this->enableUserFilter();  
        $this->assertEquals(array('Tim'), $this->getRepositoryFindAll());

        $this->fc->disable('testFilter');
        $this->assertEquals(array('John', 'Tim'), $this->getRepositoryFindAll());
    }

    protected function getRepositoryFindAll(){
        $all = $this->dm->getRepository('Documents\User')->findAll();

        $usernames = array();
        foreach($all as $user){
            $usernames[] = $user->getUsername();
        }
        sort($usernames);
        return $usernames;
    }
        
    public function testReferenceMany(){
        $this->assertEquals(array('groupA', 'groupB'), $this->getReferenceMany());

        $this->enableGroupFilter();  
        $this->assertEquals(array('groupA'), $this->getReferenceMany());

        $this->fc->disable('testFilter');
        $this->assertEquals(array('groupA', 'groupB'), $this->getReferenceMany());        
    }
    
    protected function getReferenceMany(){
        $tim = $this->dm->getRepository('Documents\User')->find($this->ids['tim']);

        $groupnames = array();
        foreach($tim->getGroups() as $group){
            if($this->dm->objectIsInitalizable($group)){
                $groupnames[] = $group->getName();
            }
        }
        sort($groupnames);
        return $groupnames;
    }
    
    public function testReferenceOne(){
        $this->assertEquals('Timothy', $this->getReferenceOne());

        $this->enableProfileFilter();  
        $this->assertEquals(null, $this->getReferenceOne());

        $this->fc->disable('testFilter');
        $this->assertEquals('Timothy', $this->getReferenceOne());        
    }
    
    protected function getReferenceOne(){
        $tim = $this->dm->getRepository('Documents\User')->find($this->ids['tim']);

        $profile = $tim->getProfile();       
        if ($profile && $this->dm->objectIsInitalizable($profile)){
            return $profile->getFirstname();
        } else {
            return null;
        }
    }

    public function testDocumentManagerRef(){
        $this->assertEquals(array('John', 'Tim'), $this->getDocumentManagerRef());
        
        $this->enableUserFilter();               
        $this->assertEquals(array('Tim'), $this->getDocumentManagerRef());

        $this->fc->disable('testFilter');
        $this->assertEquals(array('John', 'Tim'), $this->getDocumentManagerRef());        
    }
    
    protected function getDocumentManagerRef(){        
        $tim = $this->dm->getReference('Documents\User', $this->ids['tim']);
        $john = $this->dm->getReference('Documents\User', $this->ids['john']);
        
        $usernames = array();
        
        if($this->dm->objectIsInitalizable($tim)){
            $usernames[] = $tim->getUsername(); 
        }
        if($this->dm->objectIsInitalizable($john)){
            $usernames[] = $john->getUsername(); 
        }
      
        sort($usernames);
        return $usernames;          
    }
    
    public function testQuery()
    {
        $this->assertEquals(array('John', 'Tim'), $this->getQuery());

        $this->enableUserFilter();  
        $this->assertEquals(array('Tim'), $this->getQuery());

        $this->fc->disable('testFilter');
        $this->assertEquals(array('John', 'Tim'), $this->getQuery());
    }

    protected function getQuery(){
        $qb = $this->dm->createQueryBuilder('Documents\User');
        $query = $qb->getQuery();
        $all = $query->execute();

        $usernames = array();
        foreach($all as $user){
            $usernames[] = $user->getUsername();
        }
        sort($usernames);
        return $usernames;
    }
}

