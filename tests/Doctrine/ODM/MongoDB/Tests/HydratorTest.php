<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Query\Query;

class HydratorTest extends BaseTest
{
    public function testHydrator()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\HydrationClosureUser');

        $user = new HydrationClosureUser();
        $this->dm->getHydratorFactory()->hydrate($user, array(
            '_id' => 1,
            'title' => null,
            'name' => 'jon',
            'birthdate' => new \DateTime('1961-01-01'),
            'referenceOne' => array('$id' => '1'),
            'referenceMany' => array(
                array(
                    '$id' => '1'
                ),
                array(
                    '$id' => '2'
                )
            ),
            'embedOne' => array('name' => 'jon'),
            'embedMany' => array(
                array('name' => 'jon')
            )
        ));

        $this->assertEquals(1, $user->id);
        $this->assertSame(null, $user->title);
        $this->assertEquals('jon', $user->name);
        $this->assertInstanceOf('DateTime', $user->birthdate);
        $this->assertInstanceOf(__NAMESPACE__.'\HydrationClosureReferenceOne', $user->referenceOne);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->referenceMany);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user->referenceMany[0]);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user->referenceMany[1]);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->embedMany);
        $this->assertEquals('jon', $user->embedOne->name);
        $this->assertEquals('jon', $user->embedMany[0]->name);
    }
    
    public function testReadOnly()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\HydrationClosureUser');

        $user = new HydrationClosureUser();
        $this->dm->getHydratorFactory()->hydrate($user, [
            '_id' => 1,
            'name' => 'maciej',
            'birthdate' => new \DateTime('1961-01-01'),
            'embedOne' => ['name' => 'maciej'],
            'embedMany' => [
                ['name' => 'maciej']
            ],
        ], [ Query::HINT_READ_ONLY => true ]);
        
        $this->assertFalse($this->uow->isInIdentityMap($user));
        $this->assertFalse($this->uow->isInIdentityMap($user->embedOne));
        $this->assertFalse($this->uow->isInIdentityMap($user->embedMany[0]));
    }
}

/** @ODM\Document */
class HydrationClosureUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string", nullable=true) */
    public $title = 'Mr.';

    /** @ODM\Field(type="string") */
    public $name;
    
    /** @ODM\Field(type="date") */
    public $birthdate;

    /** @ODM\ReferenceOne(targetDocument="HydrationClosureReferenceOne") */
    public $referenceOne;

    /** @ODM\ReferenceMany(targetDocument="HydrationClosureReferenceMany") */
    public $referenceMany = array();

    /** @ODM\EmbedOne(targetDocument="HydrationClosureEmbedOne") */
    public $embedOne;

    /** @ODM\EmbedMany(targetDocument="HydrationClosureEmbedMany") */
    public $embedMany = array();
}

/** @ODM\Document */
class HydrationClosureReferenceOne
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class HydrationClosureReferenceMany
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\EmbeddedDocument */
class HydrationClosureEmbedMany
{
    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\EmbeddedDocument */
class HydrationClosureEmbedOne
{
    /** @ODM\Field(type="string") */
    public $name;
}
