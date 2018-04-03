<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH628Test extends BaseTest
{
    public function testQueryBuilderShouldOnlyPrepareFirstPartOfRawFields()
    {
        $query = $this->dm->createQueryBuilder(GH628Document::class)
            ->field('foo.bar.baz')->equals(1)
            ->getQuery()
            ->getQuery();

        $expected = ['f.bar.baz' => 1];

        $this->assertEquals($expected, $query['query']);
    }
}

/** @ODM\Document */
class GH628Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(name="f", type="raw") */
    public $foo;
}
