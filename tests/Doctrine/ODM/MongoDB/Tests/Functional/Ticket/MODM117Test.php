<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

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

/** @ODM\Document */
class MODM117User
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $first_name;

    /** @ODM\Field(type="string", name="last_name") */
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