<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH895Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testQueryWithSelect()
    {
        $d = new GH895Document();
        $d->a = "a";
        $d->b = "b";
        $d->c = "c";
        $d->d = "d";
        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();
        
        $r = $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH895Document');
        $qb = $r->createQueryBuilder()
                ->select('a', 'b', 'c')
                ->field('a')->equals('a');
        $doc = $qb->getQuery()->getSingleResult();
        $this->assertEquals($doc->a, $d->a);
        $this->assertEquals($doc->b, $d->b);
        $this->assertEquals($doc->c, $d->c);
        $this->assertNull($doc->d);
        
        $qb = $r->createQueryBuilder()
                ->select('b', 'c', 'd')
                ->field('d')->equals('d');
        $doc = $qb->getQuery()->getSingleResult();
        $this->assertEquals($doc->b, $d->b);
        $this->assertEquals($doc->c, $d->c);
        $this->assertEquals($doc->d, $d->d);
    }
}

/**
 * @ODM\Document
 */
class GH895Document
{
    /** @ODM\Id(strategy="auto") */
    private $id;
    
    /** @ODM\String */
    public $a;
    
    /** @ODM\String */
    public $b;
    
    /** @ODM\String */
    public $c;
    
    /** @ODM\String */
    public $d;
}
