<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class HydratorTest extends BaseTest
{
    public function testHydrator()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\HydrationClosureUser');

        $user = new HydrationClosureUser();
        $this->dm->getHydratorFactory()->hydrate($class, $user, array(
            '_id' => 1,
            'name' => 'jon',
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
        $this->assertEquals('jon', $user->name);
        $this->assertInstanceOf(__NAMESPACE__.'\HydrationClosureReferenceOne', $user->referenceOne);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->referenceMany);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user->referenceMany[0]);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user->referenceMany[1]);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\PersistentCollection', $user->embedMany);
        $this->assertEquals('jon', $user->embedOne->name);
        $this->assertEquals('jon', $user->embedMany[0]->name);
    }
}

/** @ODM\Document */
class HydrationClosureUser
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

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

    /** @ODM\String */
    public $name;
}

/** @ODM\Document */
class HydrationClosureReferenceMany
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;
}

/** @ODM\EmbeddedDocument */
class HydrationClosureEmbedMany
{
    /** @ODM\String */
    public $name;
}

/** @ODM\EmbeddedDocument */
class HydrationClosureEmbedOne
{
    /** @ODM\String */
    public $name;
}