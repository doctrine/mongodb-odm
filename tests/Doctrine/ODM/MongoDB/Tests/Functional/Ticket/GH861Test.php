<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\MongoDB\Event\UpdateEventArgs;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH861Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();


        $listener = new GH861EventListener();
        $evm = $this->dm->getEventManager();
        $evm->addEventListener(Events::prePersist, $listener);
        $evm->addEventListener(Events::preUpdate, $listener);
    }

    public function testRefreshNbVideosOnInsertFromHero()
    {
        $repositoryHero = $this->dm->getRepository(__NAMESPACE__ . '\Hero');
        $repositoryVideo = $this->dm->getRepository(__NAMESPACE__ . '\Video');

        $this->initCase1();

        $hero1  = $repositoryHero->findOneByName('hero_1');
        $video1 = $repositoryVideo->findOneByName('video_1');
        $video2 = $repositoryVideo->findOneByName('video_2');

        $this->assertCount(2, $hero1->getVideos(), "The hero contains 2 videos");
        $this->assertEquals($hero1->getNbVideos(), count($hero1->getVideos()), "The hero nbVideos is equal to the count of videos collection");

        $this->assertNotNull($video1->getHero(), "The video1 is associated to a hero");
        $this->assertEquals($hero1->getName(), $video1->getHero()->getName(), "The video1 is associated to the hero 1");

        $this->assertNotNull($video2->getHero(), "The video2 is associated to a hero");
        $this->assertEquals($hero1->getName(), $video2->getHero()->getName(), "The video2 is associated to the hero 1");
    }

    public function testRefreshNbVideosOnUpdateFromHero()
    {
        $repositoryHero = $this->dm->getRepository(__NAMESPACE__ . '\Hero');
        $repositoryVideo = $this->dm->getRepository(__NAMESPACE__ . '\Video');

        $this->initCase2();

        $hero1  = $repositoryHero->findOneByName('hero_1');
        $video1 = $repositoryVideo->findOneByName('video_1');
        $video2 = $repositoryVideo->findOneByName('video_2');
        $video3 = $repositoryVideo->findOneByName('video_3');
        $video4 = $repositoryVideo->findOneByName('video_4');
        $video5 = $repositoryVideo->findOneByName('video_5');

        $this->assertCount(3, $hero1->getVideos(), "The hero contains 3 videos");
        $this->assertEquals($hero1->getNbVideos(), count($hero1->getVideos()), "The hero nbVideos is equal to the count of videos collection");


        $this->assertNull($video1->getHero(), "The video1 is not associated to a hero");
        $this->assertNull($video2->getHero(), "The video2 is not associated to a hero");

        $this->assertNotNull($video3->getHero(), "The video3 is associated to a hero");
        $this->assertEquals($hero1->getName(), $video3->getHero()->getName(), "The video3 is associated to the hero 1");

        $this->assertNotNull($video4->getHero(), "The video4 is associated to a hero");
        $this->assertEquals($hero1->getName(), $video4->getHero()->getName(), "The video4 is associated to the hero 1");

        $this->assertNotNull($video5->getHero(), "The video5 is associated to a hero");
        $this->assertEquals($hero1->getName(), $video5->getHero()->getName(), "The video5 is associated to the hero 1");
    }

    private function initCase1()
    {
        $hero1 = new Hero('hero_1');
        $video1 = new Video('video_1');
        $video2 = new Video('video_2');

        // Add videos to hero (both ways)
        $video1->setHero($hero1);
        $hero1->addVideo($video2);

        $this->dm->persist($hero1);
        $this->dm->flush();
        $this->dm->clear();
    }

    private function initCase2()
    {
        $this->initCase1();
        $repositoryHero = $this->dm->getRepository(__NAMESPACE__ . '\Hero');
        $repositoryVideo = $this->dm->getRepository(__NAMESPACE__ . '\Video');

        $hero1  = $repositoryHero->findOneByName('hero_1');
        $video1 = $repositoryVideo->findOneByName('video_1');
        $video2 = $repositoryVideo->findOneByName('video_2');
        $video3 = new Video('video_3');
        $video4 = new Video('video_4');
        $video5 = new Video('video_5');

        // Add videos to hero (both ways)
        $video3->setHero($hero1);
        $hero1->addVideo($video4);
        $hero1->addVideo($video5);

        // remove videos from hero (both ways)
        $video1->setHero(null);
        $hero1->removeVideo($video2);

        $this->dm->flush();
        $this->dm->clear();
    }
}

class GH861EventListener
{
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->refreshHeroNbVideos($eventArgs);
    }

    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->refreshHeroNbVideos($eventArgs);
    }

    private function refreshHeroNbVideos(LifecycleEventArgs $eventArgs)
    {
        $document = $eventArgs->getDocument();
        $dm = $eventArgs->getDocumentManager();

        if (!$document || !$document instanceof Hero) {
            return;
        }

        $isUpdate = $eventArgs instanceof UpdateEventArgs;

        $document->setNbVideos(count($document->getVideos()));
        $class = $dm->getClassMetadata(get_class($document));
        if ($isUpdate) {
            $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($class, $document);
        }
    }
}

/** @ODM\Document */
class Hero
{
    /**
     * @var string Id of the Hero
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string Name of the Hero
     * @ODM\String
     */
    protected $name;

    /**
     * @var ArrayCollection
     * @ODM\ReferenceMany(targetDocument="Video", mappedBy="hero", cascade={"all"})
     */
    protected $videos;

    /**
     * @var integer
     * @ODM\Int
     */
    protected $nbVideos = 0;

    public function __construct($name)
    {
        $this->name = $name;
        $this->videos = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set nbVideos
     *
     * @param int $nbVideos
     *
     * @return self
     */
    public function setNbVideos($nbVideos)
    {
        $this->nbVideos = $nbVideos;
    }

    /**
     * Get nbVideos
     *
     * @return int $nbVideos
     */
    public function getNbVideos()
    {
        return $this->nbVideos;
    }


    /**
     * @return ArrayCollection
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * Add a video
     *
     * @param Video $video
     */
    public function addVideo(Video $video)
    {
        if ($this->videos->contains($video)) {
            return;
        }

        if ($this->videos->add($video)) {
            $video->setHero($this);
        }
    }

    /**
     * Remove a video
     *
     * @param Video $video
     */
    public function removeVideo(Video $video)
    {
        if ($this->videos->removeElement($video)) {
            $video->setHero(null);
        }
    }
}

/** @ODM\Document */
class Video
{
    /**
     * @var int Id of the Video
     * @ODM\Id
     */
    protected $id;

    /**
     * @var string Name of the Video
     * @ODM\String
     */
    protected $name;

    /**
     * @var Hero
     * @ODM\Document
     * @ODM\ReferenceOne(targetDocument="Hero", inversedBy="videos")
     */
    protected $hero;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Hero $hero
     */
    public function setHero(Hero $hero = null)
    {
        if ($this->hero === $hero) {
            return;
        }

        if ($this->hero !== null) {
            $this->hero->removeVideo($this);
        }

        $this->hero = $hero;

        if (null !== $hero) {
            $hero->addVideo($this);
        }
    }

    /**
     * @return Hero
     */
    public function getHero()
    {
        return $this->hero;
    }
}
