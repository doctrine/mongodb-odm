<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM50Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $image = new MODM50Image(__DIR__ . '/MODM50/test.txt');
        $this->dm->persist($image);
        $this->dm->flush();

        $this->assertInstanceOf('Doctrine\MongoDB\GridFSFile', $image->file);
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({
 *      "file"="MODM50File",
 *      "image"="MODM50Image"
 * })
 */
class MODM50File
{
    /** @ODM\Id */
    public $id;

    /** @ODM\File */
    public $file;

    function __construct($file) {$this->file = $file;}
}

/** @ODM\Document */
class MODM50Image extends MODM50File
{
}