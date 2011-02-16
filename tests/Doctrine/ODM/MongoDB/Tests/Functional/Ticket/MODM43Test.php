<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

class MODM43Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $person = array(
            'name' => 'Jonathan Wage'
        );
        $this->dm->getConnection()->modm43_test->people->insert($person);
        $user = $this->dm->find(__NAMESPACE__.'\Person', $person['_id']);
        $this->assertEquals('Jonathan', $user->firstName);
        $this->assertEquals('Wage', $user->lastName);
    }
}

/** @Document(db="modm43_test", collection="people") @HasLifecycleCallbacks */
class Person
{
    /** @Id */
    public $id;

    /** @String */
    public $firstName;

    /** @String */
    public $lastName;

    /** @PreLoad */
    public function preLoad(array &$data)
    {
        if (isset($data['name'])) {
            $e = explode(' ', $data['name']);
            $data['firstName'] = $e[0];
            $data['lastName'] = $e[1];
        }
    }
}