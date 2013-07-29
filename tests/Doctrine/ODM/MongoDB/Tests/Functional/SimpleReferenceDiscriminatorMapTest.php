<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;

class SimpleReferenceDiscriminatorMapTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\LameQuestion');
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\SuperQuestion');
        $this->dm->getSchemaManager()->ensureDocumentIndexes(__NAMESPACE__ . '\Stream');
    }

    /**
     * This test demonstrates a how discriminator maps are ignored when simple refs are used.
     */
    public function testReferenceDiscriminators()
    {
        $this->dm->persist($lameQuestion = new LameQuestion(['go' => 'lame']));
        $this->dm->persist($superQuestion = new SuperQuestion(['go' => 'super']));
        $this->dm->persist($stream = new Stream([$lameQuestion, $superQuestion]));

        $this->dm->flush();
        $this->dm->clear();

        $streamDoc = $this->dm->find(__NAMESPACE__ . "\Stream", $stream->getId());
        foreach ($streamDoc->getQuestions() as $q) {
            switch ($q->getType()) {
            case 'lame':
                $this->assertEquals(['go' => 'lame'], $q->getLame());
                break;
            case 'super':
                $this->assertEquals(['go' => 'super'], $q->getSuper());
                break;
            default:
                $this->assertTrue(
                    false,
                    sprintf("%s is a proxy of an abstract class, discriminator is ignored", get_class($q)));
            }
        }
    }
}

/**
* @ODM\Document(collection="rdt_action")
* @ODM\InheritanceType("SINGLE_COLLECTION")
* @ODM\DiscriminatorField(fieldName="discriminator")
* @ODM\DiscriminatorMap({"super_question"="SuperQuestion", "lame_question"="LameQuestion"})
*/
abstract class Question
{
    /** @ODM\Id */
    protected $id;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
    }
}

/** @ODM\Document */
class SuperQuestion extends Question
{
    /**
     * @ODM\Hash
     **/
    protected $super = array();

    public function __construct(array $super = array())
    {
        parent::__construct();
        $this->super = $super;
    }

    public function getSuper()
    {
        return $this->super;
    }

    public function getType()
    {
        return 'super';
    }
}

/** @ODM\Document */
class LameQuestion extends Question
{
    /**
     * @ODM\Hash
     **/
    protected $lame = array();

    public function __construct(array $lame = array())
    {
        parent::__construct();
        $this->lame = $lame;
    }

    public function getLame()
    {

        return $this->lame;
    }

    public function getType()
    {
        return 'lame';
    }
}

/** @ODM\Document */
class Stream
{
    /** @ODM\Id */
    protected $id;

    /**
     * ODM\ReferenceMany(simple="true")
     * ODM\ReferenceMany()
     * @ODM\ReferenceMany(targetDocument="Question", simple="true")
     *   simple="true",
     *
     * ODM\ReferenceMany(
     *   discriminatorMap={
     *     "super_question"="SuperQuestion",
     *     "lame_question"="LameQuestion"
     *   }
     * )
    */
    protected $actions;

    public function __construct(array $actions)
    {
        $this->actions = new ArrayCollection($actions);
    }

    public function getId()
    {
        return $this->id;
    }

    public function addQuestion(Question $action)
    {
        $this->actions[] = $action;

        return $this;
    }

    public function removeQuestion(Question $action)
    {
        $this->actions->removeElement($action);

        return $this;
    }

    public function getQuestions()
    {
        return $this->actions;
    }
}
