<?php

namespace Doctrine\ODM\MongoDB\Tests;

class HydratorTest extends BaseTest
{
    public function testHydrator()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\HydrationClosureUser');

        $user = new HydrationClosureUser();
        $this->dm->getHydratorFactory()->hydrate($user, array(
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

/** @Document */
class HydrationClosureUser
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @ReferenceOne(targetDocument="HydrationClosureReferenceOne") */
    public $referenceOne;

    /** @ReferenceMany(targetDocument="HydrationClosureReferenceMany") */
    public $referenceMany = array();

    /** @EmbedOne(targetDocument="HydrationClosureEmbedOne") */
    public $embedOne;

    /** @EmbedMany(targetDocument="HydrationClosureEmbedMany") */
    public $embedMany = array();
}

/** @Document */
class HydrationClosureReferenceOne
{
    /** @Id */
    public $id;

    /** @String */
    public $name;
}

/** @Document */
class HydrationClosureReferenceMany
{
    /** @Id */
    public $id;

    /** @String */
    public $name;
}

/** @EmbeddedDocument */
class HydrationClosureEmbedMany
{
    /** @String */
    public $name;
}

/** @EmbeddedDocument */
class HydrationClosureEmbedOne
{
    /** @String */
    public $name;
}