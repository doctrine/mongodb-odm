<?php

namespace Doctrine\ODM\MongoDB\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

use Doctrine\ODM\MongoDB\MongoCursor;

class MongoCursorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getMongoCursor($find = array())
    {
        $cursor = $this->dm->getDocumentCollection('Documents\User')->find($find);
        return new MongoCursor(
            $this->dm,
            $this->dm->getHydrator(),
            $this->dm->getClassMetadata('Documents\User'),
            $cursor
        );
    }

    public function setUp()
    {
        parent::setUp();

        $user = new \Documents\User();
        $user->setUsername('joncursor');
        $this->dm->persist($user);
        $this->dm->flush();
        
        $this->cursor = $this->getMongoCursor(array('username' => 'joncursor'));
    }

    public function testApi()
    {
        $this->assertInstanceOf('MongoCursor', $this->cursor->getMongoCursor());

        $this->cursor->hydrate(false);
        $this->assertFalse($this->cursor->hydrate());

        $this->cursor->hydrate(true);
        $this->assertTrue($this->cursor->hydrate());
        $this->assertTrue(is_array($this->cursor->getResults()));
        $this->assertInstanceOf('Documents\User', $this->cursor->getSingleResult());
    }

    public function testCountableInterface()
    {
        $this->assertEquals($this->cursor->count(), 1);
        $this->assertEquals(count($this->cursor), 1);
    }

    public function testIterableInterface()
    {
        $success = false;
        foreach ($this->cursor as $doc) {
            $success = true;
        }
        $this->assertTrue($success);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testBadMethodCall()
    {
        $this->cursor->noMethodExists();
    }

    public function testCall()
    {
        $this->cursor->explain();
    }
}