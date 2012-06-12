<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\User;

class QueryFilterTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFilter()
    {
        $tim = new User();
        $tim->setUsername('Tim');
        $this->dm->persist($tim);
        
        $john = new User();
        $john->setUsername('John');
        $this->dm->persist($john);
        
        $this->dm->flush();
               
        $this->assertEquals(array('Tim', 'John'), $this->getUsernameArray());
        
        $this->dm->getFilterCollection()->enable('testFilter');
        $this->assertEquals(array('Tim'), $this->getUsernameArray()); 
        
        $this->dm->getFilterCollection()->disable('testFilter');
        $this->assertEquals(array('Tim', 'John'), $this->getUsernameArray());         
    }
    
    protected function getUsernameArray(){
        $all = $this->dm->getRepository('Documents\User')->findAll();

        $usernames = array();
        foreach($all as $user){
            $usernames[] = $user->getUsername();
        }
        return $usernames;
    }
}

