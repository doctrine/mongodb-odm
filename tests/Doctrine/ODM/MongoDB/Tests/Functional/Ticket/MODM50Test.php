<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

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
 * @Document(collection="files", db="modm50_tests")
 * @InheritanceType("SINGLE_COLLECTION")
 * @DiscriminatorField(fieldName="type")
 * @DiscriminatorMap({
 *      "file"="MODM50File",
 *      "image"="MODM50Image"
 * })
 */
class MODM50File
{
    /** @Id */
    public $id;

    /** @File */
    public $file;

    function __construct($file) {$this->file = $file;}
}

/** @Document(collection="files", db="modm50_tests") */
class MODM50Image extends MODM50File
{
}