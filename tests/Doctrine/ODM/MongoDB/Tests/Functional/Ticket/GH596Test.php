<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH596Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->dm->getFilterCollection()->enable('testFilter');
        $filter = $this->dm->getFilterCollection()->getFilter('testFilter');
        $filter->setParameter('class', __NAMESPACE__ . '\GH596Document');
        $filter->setParameter('field', 'deleted');
        $filter->setParameter('value', false);
    }

    public function testExpressionPreparationDoesNotInjectFilterCriteria()
    {
        $class = __NAMESPACE__ . '\GH596Document';

        $repository = $this->dm->getRepository($class);
        $qb = $repository->createQueryBuilder();
        $qb->addOr($qb->expr()->field('name')->equals('foo'));
        $qb->addOr($qb->expr()->field('name')->equals('bar'));

        $query = $qb->getQuery();
        $query = $query->getQuery();

        $expected = array('$and' => array(
            array('$or' => array(
                array('name' => 'foo'),
                array('name' => 'bar'),
            )),
            array('deleted' => false),
        ));

        $this->assertEquals($expected, $query['query']);
    }
}

/** @ODM\Document */
class GH596Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="bool") */
    public $deleted = false;
}
