<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\Attributes\TestWith;

class GH2251Test extends BaseTestCase
{
    #[TestWith(['groups'])]
    #[TestWith(['groupsSimple'])]
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
