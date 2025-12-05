<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Documents\User;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(100)]
#[Iterations(5)]
final class HydrateDocumentBench extends BaseBench
{
    /** @var array<string, mixed> */
    private static array $data;

    /** @var array<string, mixed> */
    private static array $extraData;

    /** @var array<string, mixed> */
    private static array $embedOneData;

    /** @var array<string, mixed[]> */
    private static array $embedManyData;

    /** @var array<string, mixed[]> */
    private static array $referenceOneData;

    /** @var array<string, mixed[]> */
    private static array $referenceManyData;

    private static HydratorInterface $hydrator;

    public function init(): void
    {
        self::$data = [
            '_id' => new ObjectId(),
            'username' => 'alcaeus',
            'createdAt' => new UTCDateTime(),
        ];

        self::$extraData = [
            'hits' => 100,
            'age' => 30,
            'nullTest' => null,
            'logs' => [
                'User logged in',
                'User updated profile',
                'User logged out',
            ],
        ];

        self::$embedOneData = [
            'address' => ['city' => 'Munich'],
        ];

        self::$embedManyData = [
            'phonenumbers' => [
                ['phonenumber' => '12345678'],
                ['phonenumber' => '12345678'],
            ],
        ];

        self::$referenceOneData = [
            'account' => [
                '$ref' => 'Account',
                '$id' => new ObjectId(),
            ],
        ];

        self::$referenceManyData = [
            'groups' => [
                [
                    '$ref' => 'Group',
                    '$id' => new ObjectId(),
                ],
                [
                    '$ref' => 'Group',
                    '$id' => new ObjectId(),
                ],
            ],
        ];

        self::$hydrator = $this
            ->getDocumentManager()
            ->getHydratorFactory()
            ->getHydratorFor(User::class);
    }

    public function benchHydrateDocument(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$extraData);
    }

    public function benchHydrateDocumentWithEmbedOne(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$embedOneData);
    }

    public function benchHydrateDocumentWithEmbedMany(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$embedManyData);
    }

    public function benchHydrateDocumentWithReferenceOne(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$referenceOneData);
    }

    public function benchHydrateDocumentWithReferenceMany(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$referenceManyData);
    }
}
