<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH880Test extends BaseTestCase
{
    public function test880(): void
    {
        $docs   = [];
        $docs[] = new GH880Document('hello', 1);
        $docs[] = new GH880Document('world', 1);
        foreach ($docs as $doc) {
            $this->dm->persist($doc);
        }

        $this->dm->flush();
        $query  = $this->dm->createQueryBuilder(GH880Document::class);
        $cursor = $query->find()->getQuery()->execute();
        foreach ($cursor as $c) {
            self::assertEquals(1, $c->category);
        }

        $query = $this->dm->createQueryBuilder(GH880Document::class);
        $query->updateMany()
            ->field('category')->equals(1)
            ->field('category')->set(3)
            ->getQuery()
            ->execute();
        $query = $this->dm->createQueryBuilder(GH880Document::class);
        // here ->refresh() was needed for the test to pass
        $cursor = $query->find()->refresh()->getQuery()->execute();
        foreach ($cursor as $c) {
            self::assertEquals(3, $c->category);
        }
    }
}

#[ODM\Document]
class GH880Document
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $status;

    /** @var int */
    #[ODM\Field(type: 'int')]
    public $category;

    public function __construct(string $status = '', int $category = 0)
    {
        $this->status   = $status;
        $this->category = $category;
    }
}
