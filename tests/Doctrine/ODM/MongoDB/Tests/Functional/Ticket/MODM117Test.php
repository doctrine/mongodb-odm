<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

class MODM117Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIssue()
    {
        $user = new MODM117User();
        $user->first_name = 'jon';
        $user->last_name = 'wage';
        $this->dm->persist($user);
        $this->dm->flush();

        $check = $this->dm->getDocumentCollection(get_class($user))->findOne();
        $this->assertEquals('jon', $check['first_name']);
        $this->assertEquals('wage', $check['last_name']);
    }
}

/** @Document */
class MODM117User
{
    /** @Id */
    public $id;

    /** @Field(type="string") */
    public $first_name;

    /** @Field(type="string", name="last_name") */
    protected $_last_name;

    public function __get($name)
    {
        return $this->{'_'.$name};
    }

    public function __set($name, $value)
    {
        $this->{'_'.$name} = $value;
    }
}