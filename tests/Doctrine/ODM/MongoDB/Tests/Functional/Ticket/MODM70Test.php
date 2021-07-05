<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function array_search;

class MODM70Test extends BaseTest
{
    public function testTest(): void
    {
        $avatar = new Avatar('Test', 1, [new AvatarPart('#000')]);

        $this->dm->persist($avatar);
        $this->dm->flush();
        $this->dm->refresh($avatar);

        $avatar->addAvatarPart(new AvatarPart('#FFF'));

        $this->dm->flush();
        $this->dm->refresh($avatar);

        $parts = $avatar->getAvatarParts();
        $this->assertCount(2, $parts);
        $this->assertEquals('#FFF', $parts[1]->getColor());
    }
}

/**
 * @ODM\Document
 */
class Avatar
{
    /** @ODM\Id */
    protected $id;

    /**
     * @ODM\Field(name="na", type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ODM\Field(name="sex", type="int")
     *
     * @var int
     */
    protected $sex;

    /**
     * @ODM\EmbedMany(
     *  targetDocument=AvatarPart::class,
     *  name="aP"
     * )
     *
     * @var array AvatarPart
     */
    protected $avatarParts;

    public function __construct($name, $sex, $avatarParts = null)
    {
        $this->name        = $name;
        $this->sex         = $sex;
        $this->avatarParts = $avatarParts;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getSex(): int
    {
        return $this->sex;
    }

    public function setSex($sex): void
    {
        $this->sex = $sex;
    }

    public function getAvatarParts()
    {
        return $this->avatarParts;
    }

    public function addAvatarPart($part): void
    {
        $this->avatarParts[] = $part;
    }

    public function setAvatarParts($parts): void
    {
        $this->avatarParts = $parts;
    }

    public function removeAvatarPart($part): void
    {
        $key = array_search($this->avatarParts, $part);
        if ($key === false) {
            return;
        }

        unset($this->avatarParts[$key]);
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class AvatarPart
{
    /**
     * @ODM\Field(name="col", type="string")
     *
     * @var string
     */
    protected $color;

    public function __construct($color = null)
    {
        $this->color = $color;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor($color): void
    {
        $this->color = $color;
    }
}
