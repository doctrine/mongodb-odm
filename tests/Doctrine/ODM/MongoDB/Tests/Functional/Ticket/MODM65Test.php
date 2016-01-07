<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM65Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $user = new MODM65User();
        $user->socialNetworkUser = new MODM65SocialNetworkUser();
        $user->socialNetworkUser->firstName = 'Jonathan';
        $user->socialNetworkUser->lastName = 'Wage';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getDocumentCollection(__NAMESPACE__.'\MODM65User')->findOne();
        $this->assertTrue(isset($user['snu']['lN']));
        $this->assertTrue(isset($user['snu']['fN']));

        $user = $this->dm->find(__NAMESPACE__.'\MODM65User', $user['_id']);
        $this->assertEquals('Jonathan', $user->socialNetworkUser->firstName);
        $this->assertEquals('Wage', $user->socialNetworkUser->lastName);
    }
}

/**
 * @ODM\Document(collection="modm65_users")
 */
class MODM65User
{
	/**
	 * @ODM\Id
	 */
	public $id;
	/**
	 * @ODM\EmbedOne(
	 * 	discriminatorField="php",
	 * 	discriminatorMap={
	 * 		"fbu"="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\MODM65SocialNetworkUser"
	 * 	},
	 * 	name="snu"
	 * )
	 */
	public $socialNetworkUser;
}

/**
 * @ODM\EmbeddedDocument
 */
class MODM65SocialNetworkUser
{
	/**
	 * @ODM\Field(name="fN", type="string")
	 * @var string
	 */
	public $firstName;
	/**
	 * @ODM\Field(name="lN", type="string")
	 * @var string
	 */
	public $lastName;
}
