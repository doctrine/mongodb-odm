<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

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
 * @Document(collection="modm65_users")
 */
class MODM65User
{
	/**
	 * @Id
	 */
	public $id;
	/**
	 * @EmbedOne(
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
 * @EmbeddedDocument
 */
class MODM65SocialNetworkUser
{
	/**
	 * @String(name="fN")
	 * @var string
	 */
	public $firstName;
	/**
	 * @String(name="lN")
	 * @var string
	 */
	public $lastName;
}