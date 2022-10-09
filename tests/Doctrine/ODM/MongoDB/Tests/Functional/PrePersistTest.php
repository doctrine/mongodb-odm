<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class PrePersistTest extends BaseTest
{
    public function testPrePersist(): void
    {
        $test = new PrePersistTestDocument();
        $this->dm->persist($test);
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->flush();

        self::assertEquals(1, $test->prePersist);

        $test->field = 'test';

        $this->dm->flush();
        $this->dm->flush();

        self::assertEquals(1, $test->preUpdate);
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class PrePersistTestDocument
{
    /** @var int */
    public $prePersist;

    /** @var int */
    public $preUpdate;

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $field;

    /** @ODM\PrePersist */
    public function prePersist(): void
    {
        $this->prePersist++;
    }

    /** @ODM\PreUpdate */
    public function preUpdate(): void
    {
        $this->preUpdate++;
    }
}
