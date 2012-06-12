<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\User;

class QueryFilterTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    protected function createUsers(){
        $tim = new User();
        $tim->setUsername('Tim');
        $this->dm->persist($tim);
        
        $john = new User();
        $john->setUsername('John');
        $this->dm->persist($john);
        
        $this->dm->flush();        
    }
    
    public function testRepositoryFilter()
    {
        $this->createUsers();
               
        $this->assertEquals(array('Tim', 'John'), $this->getRepositoryUsernameArray());
        
        $this->dm->getFilterCollection()->enable('testFilter');
        $this->assertEquals(array('Tim'), $this->getRepositoryUsernameArray()); 
        
        $this->dm->getFilterCollection()->disable('testFilter');
        $this->assertEquals(array('Tim', 'John'), $this->getRepositoryUsernameArray());         
    }
        
    public function testQueryFilter()
    {
        $this->createUsers();
        
        $this->assertEquals(array('Tim', 'John'), $this->getQueryUsernameArray());
        
        $this->dm->getFilterCollection()->enable('testFilter');
        $this->assertEquals(array('Tim'), $this->getQueryUsernameArray()); 
        
        $this->dm->getFilterCollection()->disable('testFilter');
        $this->assertEquals(array('Tim', 'John'), $this->getQueryUsernameArray());         
    }
    
    protected function getRepositoryUsernameArray(){
        $all = $this->dm->getRepository('Documents\User')->findAll();

        $usernames = array();
        foreach($all as $user){
            $usernames[] = $user->getUsername();
        }
        return $usernames;
    }
    
    protected function getQueryUsernameArray(){        
        $qb = $this->dm->createQueryBuilder('Documents\User');        
        $query = $qb->getQuery();
        $all = $query->execute(); 

        $usernames = array();
        foreach($all as $user){
            $usernames[] = $user->getUsername();
        }
        return $usernames;        
    }    
}

