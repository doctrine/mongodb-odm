<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use MongoDB\BSON\ObjectId;

class GH2251Test extends BaseTest
{
    /**
     * @testWith ["groups"]
     *           ["groupsSimple"]
     */
    public function testElemMatchQuery(string $fieldName): void
    {
        $builder = $this->dm->createQueryBuilder(User::class);

        $objectIds = [
            new ObjectId('5fae9a775ef4492e3c72b3f3'),
            new ObjectId('5fae9a775ef4492e3c72b3f4'),
        ];

        $notIn     = $builder->expr()->notIn($objectIds);
        $elemMatch = $builder->expr()
            ->field($fieldName)
            ->elemMatch($notIn);

        $builder->addNor(
            $elemMatch,
        );

        self::assertSame(
            [
                '$nor' => [
                    [
                        $fieldName => [
                            '$elemMatch' => ['$nin' => $objectIds],
                        ],
                    ],
                ],
            ],
            $builder->getQueryArray(),
        );
    }
}
