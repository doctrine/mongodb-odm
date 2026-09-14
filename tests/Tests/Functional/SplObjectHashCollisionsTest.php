<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use ReflectionObject;

class SplObjectHashCollisionsTest extends BaseTestCase
{
    /** @param callable(DocumentManager, object=): void $f */
    #[DataProvider('provideParentAssociationsIsCleared')]
    public function testParentAssociationsIsCleared(callable $f): void
    {
        $d         = new SplColDoc();
        $d->one    = new SplColEmbed('d.one.v1');
        $d->many[] = new SplColEmbed('d.many.0.v1');
        $d->many[] = new SplColEmbed('d.many.1.v1');

        $this->dm->persist($d);
        $this->expectCount(3);
        $f($this->dm, $d);
        $this->expectCount(0);
    }

    /** @param callable(DocumentManager, object=): void $f */
    #[DataProvider('provideParentAssociationsIsCleared')]
    public function testParentAssociationsLeftover(callable $f, int $leftover): void
    {
        $d         = new SplColDoc();
        $d->one    = new SplColEmbed('d.one.v1');
        $d->many[] = new SplColEmbed('d.many.0.v1');
        $d->many[] = new SplColEmbed('d.many.1.v1');
        $this->dm->persist($d);
        $d->one = new SplColEmbed('d.one.v2');
        $this->dm->flush();

        $this->expectCount(4);
        $f($this->dm, $d);
        $this->expectCount($leftover);
    }

    public static function provideParentAssociationsIsCleared(): array
    {
        return [
            [
                static function (DocumentManager $dm): void {
                    $dm->clear();
                },
                0,
            ],
            [
                static function (DocumentManager $dm, $doc): void {
                    $dm->detach($doc);
                },
                1,
            ],
        ];
    }

    #[IgnoreDeprecations]
    public function testParentAssociationsLeftoverPartialClear(): void
    {
        $this->testParentAssociationsLeftover(
            static function (DocumentManager $dm): void {
                $dm->clear(SplColDoc::class);
            },
            1,
        );
    }

    private function expectCount(int $expected): void
    {
        self::assertSame($expected, $this->countObjectStatesWithParentAssociation());
    }

    private function countObjectStatesWithParentAssociation(): int
    {
        $dmReflection       = new ReflectionObject($this->dm);
        $registry           = $dmReflection->getProperty('documentRegistry')->getValue($this->dm);
        $registryReflection = new ReflectionObject($registry);
        $storage            = $registryReflection->getProperty('objectStates')->getValue($registry);
        $withData           = 0;

        foreach ($storage as $document) {
            if ($storage[$document]->parentAssociation !== null) {
                $withData++;
            }
        }

        return $withData;
    }
}

#[ODM\Document]
class SplColDoc
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var object|null */
    #[ODM\EmbedOne]
    public $one;

    /** @var Collection<int, object>|array<object> */
    #[ODM\EmbedMany]
    public $many = [];
}

#[ODM\EmbeddedDocument]
class SplColEmbed
{
    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
