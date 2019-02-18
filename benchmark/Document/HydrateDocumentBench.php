<?php

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Hydrator\HydratorInterface;
use Documents\User;
use MongoDate;
use MongoId;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;

/**
 * @BeforeMethods({"init"}, extend=true)
 */
final class HydrateDocumentBench extends BaseBench
{
    /**
     * @var array
     */
    private static $data;

    /**
     * @var array
     */
    private static $embedOneData;

    /**
     * @var array
     */
    private static $embedManyData;

    /**
     * @var array
     */
    private static $referenceOneData;

    /**
     * @var array
     */
    private static $referenceManyData;

    /**
     * @var HydratorInterface
     */
    private static $hydrator;

    public function init()
    {
        self::$data = [
            '_id' => new MongoId(),
            'username' => 'alcaeus',
            'createdAt' => new MongoDate(),
        ];

        self::$embedOneData = [
            'address' => [
                'city' => 'Munich',
            ],
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
                '$id' => new MongoId(),
            ],
        ];

        self::$referenceManyData = [
            'groups' => [
                [
                    '$ref' => 'Group',
                    '$id' => new MongoId(),
                ],
                [
                    '$ref' => 'Group',
                    '$id' => new MongoId(),
                ],
            ],
        ];

        self::$hydrator = $this
            ->getDocumentManager()
            ->getHydratorFactory()
            ->getHydratorFor(User::class);
    }

    /**
     * @Warmup(2)
     */
    public function benchHydrateDocument()
    {
        self::$hydrator->hydrate(new User(), self::$data);
    }

    /**
     * @Warmup(2)
     */
    public function benchHydrateDocumentWithEmbedOne()
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$embedOneData);
    }

    /**
     * @Warmup(2)
     */
    public function benchHydrateDocumentWithEmbedMany()
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$embedManyData);
    }

    /**
     * @Warmup(2)
     */
    public function benchHydrateDocumentWithReferenceOne()
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$referenceOneData);
    }

    /**
     * @Warmup(2)
     */
    public function benchHydrateDocumentWithReferenceMany()
    {
        self::$hydrator->hydrate(new User(), self::$data + self::$referenceManyData);
    }
}
