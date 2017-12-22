<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

class MODM43Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $person = array(
            '_id' => new ObjectId(),
            'name' => 'Jonathan Wage'
        );
        $this->dm->getDocumentCollection(__NAMESPACE__.'\Person')->insertOne($person);
        $user = $this->dm->find(__NAMESPACE__.'\Person', $person['_id']);
        $this->assertEquals('Jonathan', $user->firstName);
        $this->assertEquals('Wage', $user->lastName);
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class Person
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $firstName;

    /** @ODM\Field(type="string") */
    public $lastName;

    /** @ODM\PreLoad */
    public function preLoad(PreLoadEventArgs $e)
    {
        $data =& $e->getData();
        if (isset($data['name'])) {
            $e = explode(' ', $data['name']);
            $data['firstName'] = $e[0];
            $data['lastName'] = $e[1];
        }
    }
}
