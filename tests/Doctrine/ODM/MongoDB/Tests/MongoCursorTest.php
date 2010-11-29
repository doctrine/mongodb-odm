<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\MongoCursor;

class MongoCursorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getMongoCursor($find = array())
    {
        $cursor = $this->dm->getDocumentCollection('Documents\User')->find($find);
        return new MongoCursor(
            $this->dm,
            $this->dm->getUnitOfWork(),
            $this->dm->getHydrator(),
            $this->dm->getClassMetadata('Documents\User'),
            $this->dm->getConfiguration(),
            $cursor
        );
    }

    public function setUp()
    {
        parent::setUp();

        $user = new \Documents\User();
        $user->setUsername('joncursor');
        $this->dm->persist($user);
        $this->dm->flush(array('safe' => true));

        $this->cursor = $this->getMongoCursor(array('username' => 'joncursor'));
    }

    public function testApi()
    {
        $this->assertInstanceOf('MongoCursor', $this->cursor->getMongoCursor());

        $this->cursor->hydrate(false);
        $this->assertFalse($this->cursor->hydrate());

        $this->cursor->hydrate(true);
        $this->assertTrue($this->cursor->hydrate());
        $this->assertInstanceOf('Documents\User', $this->cursor->getSingleResult());
        $this->assertTrue(is_array($this->cursor->toArray()));
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

    public function testCall()
    {
        $this->cursor->explain();
    }
}