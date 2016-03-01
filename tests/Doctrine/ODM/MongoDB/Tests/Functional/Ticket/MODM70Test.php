<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM70Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
		$avatar = new Avatar('Test', 1, array(new AvatarPart('#000')));

		$this->dm->persist($avatar);
		$this->dm->flush();
		$this->dm->refresh($avatar);

		$avatar->addAvatarPart(new AvatarPart('#FFF'));

		$this->dm->flush();
		$this->dm->refresh($avatar);

		$parts = $avatar->getAvatarParts();
		$this->assertEquals(2, count($parts));
		$this->assertEquals('#FFF', $parts[1]->getColor());
    }
}

/**
 * @ODM\Document
 */
class Avatar
{

	/**
	 * @ODM\Id
	 */
	protected $id;

	/**
	 * @ODM\Field(name="na", type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @ODM\Field(name="sex", type="int")
	 * @var int
	 */
	protected $sex;

	/**
	 * @ODM\EmbedMany(
	 *	targetDocument="AvatarPart",
	 *	name="aP"
	 * )
	 * @var array AvatarPart
	 */
	protected $avatarParts;

	public function __construct($name, $sex, $avatarParts = null)
	{
		$this->name = $name;
		$this->sex = $sex;
		$this->avatarParts = $avatarParts;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getSex()
	{
		return $this->sex;
	}

	public function setSex($sex)
	{
		$this->sex = $sex;
	}

	public function getAvatarParts()
	{
		return $this->avatarParts;
	}

	public function addAvatarPart($part)
	{
		$this->avatarParts[] = $part;
	}

	public function setAvatarParts($parts)
	{
		$this->avatarParts = $parts;
	}

	public function removeAvatarPart($part)
	{
		$key = array_search($this->avatarParts, $part);
		if ($key !== false) {
			unset($this->avatarParts[$key]);
		}
	}
}

/**
 * @ODM\EmbeddedDocument
 */
class AvatarPart
{
	/**
	 * @ODM\Field(name="col", type="string")
	 * @var string
	 */
	protected $color;

	public function __construct($color = null)
	{
		$this->color = $color;
	}

	public function getColor()
	{
		return $this->color;
	}

	public function setColor($color)
	{
		$this->color = $color;
	}
}
