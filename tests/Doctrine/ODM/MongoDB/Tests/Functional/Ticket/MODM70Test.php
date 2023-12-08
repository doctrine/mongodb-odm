<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

use function array_search;

class MODM70Test extends BaseTestCase
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
        self::assertCount(2, $parts);
        self::assertEquals('#FFF', $parts[1]->getColor());
    }
}

#[ODM\Document]
class Avatar
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string */
    #[ODM\Field(name: 'na', type: 'string')]
    protected $name;

    /** @var int */
    #[ODM\Field(name: 'sex', type: 'int')]
    protected $sex;

    /** @var Collection<int, AvatarPart>|array<AvatarPart> */
    #[ODM\EmbedMany(targetDocument: AvatarPart::class, name: 'aP')]
    protected $avatarParts;

    /** @param AvatarPart[] $avatarParts */
    public function __construct(string $name, int $sex, ?array $avatarParts = null)
    {
        $this->name        = $name;
        $this->sex         = $sex;
        $this->avatarParts = $avatarParts;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSex(): int
    {
        return $this->sex;
    }

    public function setSex(int $sex): void
    {
        $this->sex = $sex;
    }

    /** @return Collection<int, AvatarPart>|array<AvatarPart>|null */
    public function getAvatarParts()
    {
        return $this->avatarParts;
    }

    public function addAvatarPart(AvatarPart $part): void
    {
        $this->avatarParts[] = $part;
    }

    /** @param AvatarPart[] $parts */
    public function setAvatarParts(array $parts): void
    {
        $this->avatarParts = $parts;
    }

    public function removeAvatarPart(AvatarPart $part): void
    {
        $key = array_search($part, $this->avatarParts);
        if ($key === false) {
            return;
        }

        unset($this->avatarParts[$key]);
    }
}

#[ODM\EmbeddedDocument]
class AvatarPart
{
    /** @var string */
    #[ODM\Field(name: 'col', type: 'string')]
    protected $color;

    public function __construct(string $color)
    {
        $this->color = $color;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }
}
