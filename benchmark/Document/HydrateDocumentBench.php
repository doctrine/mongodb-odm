<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Documents\User;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Warmup;

#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
final class HydrateDocumentBench extends BaseBench
{
    /** @var array<string, mixed> */
    private static array $data;

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

    #[Warmup(2)]
    public function benchHydrateDocument(): void
    {
        self::$hydrator->hydrate(new User(), self::$data);
    }

    #[Warmup(2)]
    public function benchHydrateDocumentWithEmbedOne(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$embedOneData);
    }

    #[Warmup(2)]
    public function benchHydrateDocumentWithEmbedMany(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$embedManyData);
    }

    #[Warmup(2)]
    public function benchHydrateDocumentWithReferenceOne(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$referenceOneData);
    }

    #[Warmup(2)]
    public function benchHydrateDocumentWithReferenceMany(): void
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$referenceManyData);
    }
}
