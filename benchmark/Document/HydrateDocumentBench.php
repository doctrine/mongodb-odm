<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\AllTypesDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\RichDocument;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Documents\User;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function array_fill;
use function array_map;
use function range;

#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(100)]
#[Iterations(2)]
final class HydrateDocumentBench extends BaseBench
{
    protected static function createMetadataDriverImpl(): AttributeDriver
    {
        return AttributeDriver::create([__DIR__ . '/../../tests/Documents', __DIR__ . '/../Fixtures']);
    }

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

    private static HydratorInterface $richDocumentHydrator;

    private static HydratorInterface $allTypesHydrator;

    /** @var array<string, mixed> */
    private static array $nestedEmbedManyData;

    /** @var array<string, mixed> */
    private static array $largeEmbedManyData;

    /** @var array<string, mixed> */
    private static array $discriminatedEmbedManyData;

    /** @var array<string, mixed> */
    private static array $allTypesData;

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

        self::$richDocumentHydrator = $this
            ->getDocumentManager()
            ->getHydratorFactory()
            ->getHydratorFor(RichDocument::class);

        self::$allTypesHydrator = $this
            ->getDocumentManager()
            ->getHydratorFactory()
            ->getHydratorFor(AllTypesDocument::class);

        self::$nestedEmbedManyData = [
            'title' => 'benchmark',
            'tags' => array_map(
                static fn (int $i) => [
                    'name' => 'tag' . $i,
                    'meta' => ['weight' => $i, 'note' => 'note for tag ' . $i],
                ],
                range(1, 3),
            ),
        ];

        self::$largeEmbedManyData = [
            'title' => 'benchmark',
            'tags' => array_map(
                static fn (int $i) => ['name' => 'tag' . $i],
                range(1, 50),
            ),
        ];

        self::$discriminatedEmbedManyData = [
            'title' => 'benchmark',
            'shapes' => [
                ['type' => 'circle', 'radius' => 1.5],
                ['type' => 'square', 'side' => 2.0],
                ['type' => 'circle', 'radius' => 3.25],
            ],
        ];

        self::$allTypesData = [
            '_id' => new ObjectId(),
            'name' => 'benchmark',
            'intValue' => 42,
            'floatValue' => 3.14,
            'boolValue' => true,
            'dateValue' => new UTCDateTime(),
            'counter' => 7,
            'tags' => array_fill(0, 5, 'tag'),
        ];
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

    public function benchHydrateDocumentWithNestedEmbedMany(): void
    {
        self::$richDocumentHydrator->hydrate(new RichDocument(), self::$nestedEmbedManyData);
    }

    public function benchHydrateDocumentWithLargeEmbedMany(): void
    {
        self::$richDocumentHydrator->hydrate(new RichDocument(), self::$largeEmbedManyData);
    }

    public function benchHydrateDocumentWithDiscriminatedEmbedMany(): void
    {
        self::$richDocumentHydrator->hydrate(new RichDocument(), self::$discriminatedEmbedManyData);
    }

    public function benchHydrateAllFieldTypes(): void
    {
        self::$allTypesHydrator->hydrate(new AllTypesDocument(), self::$allTypesData);
    }
}
