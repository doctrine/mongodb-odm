<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\Criteria;

/**
 * @author Rudolph Gottesheim <r.gottesheim@loot.at>
 */
class DocumentRepositoryTest extends BaseTest
{
    public function testMatchingAcceptsCriteriaWithNullWhereExpression()
    {
        $repository = $this->dm->getRepository('Documents\User');
        $criteria = new Criteria();

        $this->assertNull($criteria->getWhereExpression());
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $repository->matching($criteria));
    }
    
    public function testFindByManyIds()
    {
        $t1 = new \Documents\Task('test');;
        $this->dm->persist($t1);
        $this->dm->flush();
        $r = $this->dm->getRepository('Documents\Task');
        
        $results = $r->findBy(array('id' => $t1->getId()));
        $this->assertCount(1, $results);
        
        $results = $r->findBy(array('id' => array($t1->getId())));
        $this->assertCount(1, $results);
    }
}
