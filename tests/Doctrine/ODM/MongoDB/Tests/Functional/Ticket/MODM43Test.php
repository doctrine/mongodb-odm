<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;
use function explode;

class MODM43Test extends BaseTest
{
    public function testTest()
    {
        $person = [
            '_id' => new ObjectId(),
            'name' => 'Jonathan Wage',
        ];
        $this->dm->getDocumentCollection(Person::class)->insertOne($person);
        $user = $this->dm->find(Person::class, $person['_id']);
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
        if (! isset($data['name'])) {
            return;
        }

        $e = explode(' ', $data['name']);
        $data['firstName'] = $e[0];
        $data['lastName'] = $e[1];
    }
}
