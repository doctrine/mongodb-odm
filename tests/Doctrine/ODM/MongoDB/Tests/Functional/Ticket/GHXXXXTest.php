<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;

class GHXXXXTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCanSnapshotSimpleModel()
    {
        $book = new Book;
        $book->setTitle("Awesome PHP");
        $book->setAuthor("Jonathan Block");
        $exported = $this->dm->export($book);
        $this->assertTrue(is_array($exported));
        $this->assertTrue(count($exported) == 3);
        $this->assertTrue(array_key_exists("id", $exported));
        $this->assertTrue(array_key_exists("title", $exported));
        $this->assertTrue(array_key_exists("author", $exported));
        $this->assertNull($exported["id"]);
        $this->assertEquals("Awesome PHP", $exported["title"]);
        $this->assertEquals("Jonathan Block", $exported["author"]);
    }

    public function testModelWithEmbeddedDocumentsCanExport()
    {
        $car = new Car;
        $car->setColor("Green");
        $drivingManual = new DrivingManual;
        $drivingManual->setTitle("Honda CRV Driver's Manual");
        $drivingManual->setNumPages(88);
        $car->addToGloveBox($drivingManual);
        $exported = $this->dm->export($car);
        $expectedArray = array(
            'id' => null,
            'color' => 'Green',
            'glovebox' => array(
                array(
                    'payload' => array(
                        'id' => null,
                        'title' => "Honda CRV Driver's Manual",
                        'numPages' => 88,
                        'pages' => array(),
                    ),
                    '__class' => 'Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\DrivingManual'
                )
            )
        );
        $this->assertEquals($expectedArray, $exported);
    }

    public function testModelWithManyDeeplyEmbeddedDocumentsCanExportAsExpected()
    {
        $car = new Car;
        $car->setColor("Green");
        $drivingManual = new DrivingManual;
        $drivingManual->setTitle("Honda CRV Driver's Manual");
        $drivingManual->setNumPages(88);
        $page1 = new Page();
        $picture1 = new Picture();
        $picture1->setFileName("goat.jpg");
        $picture2 = new Picture();
        $picture2->setFileName("horse.jpg");
        $page1->addPicture($picture1);
        $page1->addPicture($picture2);
        $page1->setPageNumber(1);
        $page2 = new Page();
        $page2->setPageNumber(2);
        $drivingManual->addPage($page1);
        $drivingManual->addPage($page2);
        $car->addToGloveBox($drivingManual);
        $exported = $this->dm->export($car);
        $expectedArray = array(
            'color' => 'Green',
            'glovebox' => array(
                array(
                    'payload' => array(
                        'id' => null,
                        'title' => "Honda CRV Driver's Manual",
                        'numPages' => 88,
                        'pages' => array(
                            array(
                                'payload' => array(
                                    'id' => null,
                                    'pageNumber' => 1,
                                    'pictures' => array(
                                        array(
                                            'payload' => array(
                                                'id' => null,
                                                'fileName' => 'goat.jpg',
                                            ),
                                            '__class' => 'Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\Picture'
                                        ),
                                        array(
                                            'payload' => array(
                                                'id' => null,
                                                'fileName' => 'horse.jpg',
                                            ),
                                            '__class' => 'Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\Picture'
                                        )
                                    )
                                ),
                                '__class' => 'Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\Page'
                            ),
                            array(
                                'payload' => array(
                                    'id' => null,
                                    'pageNumber' => 2,
                                    'pictures' => array()
                                ),
                                '__class' => 'Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\Page'
                            )
                        ),
                    ),
                    '__class' => 'Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\DrivingManual'
                )
            ),
            "id" => null
        );
        $this->assertEquals($expectedArray, $exported);
    }

    public function testDeepCompare()
    {
        $car = $this->buildComplexCarModel();
        $car->setId('a'); // Assign a custom id.

        $this->dm->persist($car);
        $this->dm->flush();
        $this->dm->clear();

        // Get the model from the database.
        $retrievedCarDocument = $this->dm->getRepository(__NAMESPACE__ . '\Car')->findOneBy(array('id' => 'a'));

        // Now lets build an exact replica of that model from scratch.
        $exactlyTheSameCar = $this->buildComplexCarModel();
        $exactlyTheSameCar->setId('a');

        $this->assertTrue($this->dm->deepCompare($retrievedCarDocument, $exactlyTheSameCar));
    }

    private function buildComplexCarModel()
    {
        $car = new Car;
        $car->setColor("Green");
        $drivingManual = new DrivingManual;
        $drivingManual->setTitle("Honda CRV Driver's Manual");
        $drivingManual->setNumPages(88);
        $page1 = new Page();
        $picture1 = new Picture();
        $picture1->setFileName("goat.jpg");
        $picture2 = new Picture();
        $picture2->setFileName("horse.jpg");
        $page1->addPicture($picture1);
        $page1->addPicture($picture2);
        $page1->setPageNumber(1);
        $page2 = new Page();
        $page2->setPageNumber(2);
        $drivingManual->addPage($page1);
        $drivingManual->addPage($page2);
        $car->addToGloveBox($drivingManual);
        return $car;
    }
}

/** @ODM\Document */
class Book
{
    /** @ODM\Id(strategy="UUID", type="string") */
    protected $id;

    /**
     * @ODM\String
     */
    protected $title;
    /**
     * @ODM\String
     */
    protected $author;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}

/** @ODM\Document */
class Car
{
    /** @ODM\Id(strategy="UUID", type="string") */
    protected $id;

    /**
     * @ODM\String
     */
    protected $color;
    /**
     * @ODM\EmbedMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\DrivingManual", strategy="set")
     */
    protected $glovebox;

    public function __construct()
    {
        $this->glovebox = new ArrayCollection();
    }

    /**
     * @param $id
     */
    public function setId($id)
    {
        return $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    public function addToGloveBox(DrivingManual $model)
    {
        $this->glovebox->add($model);
    }

    /**
     * @return mixed
     */
    public function getGlovebox()
    {
        return $this->glovebox;
    }

    /**
     * @param mixed $glovebox
     */
    public function setGlovebox($glovebox)
    {
        $this->glovebox = $glovebox;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class DrivingManual
{
    /**
     * @ODM\String
     */
    protected $id;
    /** @ODM\String */
    protected $title;
    /** @ODM\Int */
    protected $numPages;
    /**
     * @ODM\EmbedMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\Page", strategy="set")
     */
    protected $pages;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getNumPages()
    {
        return $this->numPages;
    }

    /**
     * @param mixed $numPages
     */
    public function setNumPages($numPages)
    {
        $this->numPages = $numPages;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param mixed $pages
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    public function addPage($page)
    {
        $this->pages->add($page);
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Page
{
    /**
     * @ODM\String
     */
    protected $id;
    /** @ODM\Int */
    protected $pageNumber;
    /**
     * @ODM\EmbedMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GHXXXX\Picture", strategy="set")
     */
    protected $pictures;

    public function __construct()
    {
        $this->pictures = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * @param mixed $pageNumber
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }

    /**
     * @return mixed
     */
    public function getPictures()
    {
        return $this->pictures;
    }

    /**
     * @param mixed $pictures
     */
    public function setPictures($pictures)
    {
        $this->pictures = $pictures;
    }

    public function addPicture($picture)
    {
        $this->pictures->add($picture);
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Picture
{
    /**
     * @ODM\String
     */
    protected $id;
    /** @ODM\String */
    protected $fileName;

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
