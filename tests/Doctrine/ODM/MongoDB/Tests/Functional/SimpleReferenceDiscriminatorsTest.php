<?php
namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class SimpleReferenceDiscriminatorsTest extends BaseTest
{
    /** @var DocumentManager */
    protected $dm;

    public function testCollectionInitializationWithProxies()
    {
        $quiz = new Quiz;
        $this->persistQuizWithQuestions($quiz);

        $receivedQuiz = $this->dm->find(get_class($quiz), $quiz->id);

        $this->assertCount(2, $receivedQuiz->questions);
        $this->assertContainsOnlyInstancesOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $receivedQuiz->questions);
        $this->assertContainsOnlyInstancesOf(__NAMESPACE__ . '\BasicQuestion', $receivedQuiz->questions);
        $this->assertFalse($receivedQuiz->questions[0] instanceof AdvancedQuestion);
        $this->assertFalse($receivedQuiz->questions[0]->__isInitialized__);
        $this->assertFalse($receivedQuiz->questions[1]->__isInitialized__);
    }

    public function testProxiesInitialization()
    {
        $quiz = new Quiz;
        list($q1, $q2) = $this->persistQuizWithQuestions($quiz);

        $receivedQuiz = $this->dm->find(get_class($quiz), $quiz->id);

        $this->assertCount(2, $receivedQuiz->questions);
        $this->assertEquals($q1->text, $receivedQuiz->questions[0]->text);
        $this->assertEquals($q2->text, $receivedQuiz->questions[1]->text);
    }

    public function testPreloadedDocumentsAreUsed()
    {
        $quiz = new Quiz;
        list($q1, $q2) = $this->persistQuizWithQuestions($quiz);

        // Collect queries to make sure we are not issuing redundant ones
        $findQueries = array();
        $logger = function($q) use (&$findQueries) {
            isset($q['find']) && $findQueries[] = $q;
        };
        $dm = $this->getNewDmWithLogger($logger);

        // Load questions to the identity map (2 queries)
        $dm->find(get_class($q1), $q1->id);
        $dm->find(get_class($q2), $q2->id);
        $this->assertCount(2, $findQueries);

        // Find the quiz — another query
        $receivedQuiz = $dm->find(get_class($quiz), $quiz->id);
        $this->assertCount(3, $findQueries);

        // Collection initialization should use loaded documents, no queries needed
        $this->assertFalse($q1 instanceof Proxy);
        $this->assertFalse($q2 instanceof Proxy);
        $this->assertEquals($q1->text, $receivedQuiz->questions[0]->text);
        $this->assertEquals($q2->text, $receivedQuiz->questions[1]->text);
        $this->assertCount(3, $findQueries);
    }

    public function testProxiesAreNotReplacedAfterOriginalDocumentLoadedToIdentityMap()
    {
        $quiz = new Quiz;
        list($q1,) = $this->persistQuizWithQuestions($quiz);

        // Find the quiz and initialize questions collection with proxies
        $receivedQuiz = $this->dm->find(get_class($quiz), $quiz->id);
        iterator_to_array($receivedQuiz->questions);

        // Load the first question separately.
        // It should be the new instance of AdvancedQuestion, not the one of proxies we just created.
        $receivedQ1 = $this->dm->find(get_class($q1), $q1->id);
        $this->assertTrue($receivedQ1 instanceof AdvancedQuestion);
        $this->assertFalse($receivedQ1 instanceof Proxy);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $receivedQuiz->questions[0]);
        $this->assertFalse($receivedQuiz->questions[0]->__isInitialized__);

        // Initialize proxy
        $receivedQuiz->questions[0]->text;

        // Since we could not replace the proxy by another instance,
        // we should have initialized proxy of BasicQuestion by now.
        $this->assertFalse($receivedQuiz->questions[0] instanceof AdvancedQuestion);
        $this->assertTrue($receivedQuiz->questions[0]->__isInitialized__);
    }

    public function testPriming()
    {
        $quiz = new Quiz;
        $this->persistQuizWithQuestions($quiz);

        $qb = $this->dm->createQueryBuilder(get_class($quiz))
            ->field('id')->equals($quiz->id)
            ->field('questions')->prime(true);
        $receivedQuiz = $qb->getQuery()->execute()->getSingleResult();

        $this->assertCount(2, $receivedQuiz->questions);
        $this->assertFalse($receivedQuiz->questions[0] instanceof Proxy);
        $this->assertTrue($receivedQuiz->questions[0] instanceof AdvancedQuestion);
        $this->assertFalse($receivedQuiz->questions[1] instanceof Proxy);
        $this->assertTrue($receivedQuiz->questions[1] instanceof BasicQuestion);
    }

    public function testReferenceOne()
    {
        $question = new BasicQuestion;
        $answer = $this->persistQuestionWithAnswer($question);

        $receivedQuestion = $this->dm->find(get_class($question), $question->id);

        $this->assertTrue($receivedQuestion->answer instanceof Proxy);
        $this->assertFalse($receivedQuestion->answer instanceof CorrectAnswer);
        $this->assertTrue($receivedQuestion->answer instanceof Answer);
        $this->assertFalse($receivedQuestion->answer->__isInitialized__);
        $this->assertEquals($answer->text, $receivedQuestion->answer->text);
    }

    public function testReferenceOnePreload()
    {
        $question = new BasicQuestion;
        $answer = $this->persistQuestionWithAnswer($question);

        $this->dm->find(get_class($answer), $answer->id);
        $receivedQuestion = $this->dm->find(get_class($question), $question->id);

        $this->assertTrue($receivedQuestion->answer instanceof CorrectAnswer);
    }

    /**
     * @param Quiz $quiz
     *
     * @return QuestionAbstract[]
     */
    private function persistQuizWithQuestions(Quiz $quiz)
    {
        $q1 = new AdvancedQuestion;
        $q2 = new BasicQuestion;
        $q1->text = 'q1 text';
        $q2->text = 'q2 text';
        $this->dm->persist($q1);
        $this->dm->persist($q2);
        $quiz->questions->add($q1);
        $quiz->questions->add($q2);
        $this->dm->persist($quiz);
        $this->dm->flush();
        $this->dm->clear();

        return array($q1, $q2);
    }

    /**
     * @param BasicQuestion $question
     *
     * @return CorrectAnswer
     */
    private function persistQuestionWithAnswer(BasicQuestion $question)
    {
        $a = new CorrectAnswer;
        $a->text = 'a text';
        $this->dm->persist($a);
        $question->answer = $a;
        $this->dm->persist($question);
        $this->dm->flush();
        $this->dm->clear();

        return $a;
    }

    /**
     * @param $logger
     *
     * @return DocumentManager
     */
    private function getNewDmWithLogger($logger)
    {
        $this->dm->getConfiguration()->setLoggerCallable($logger);
        $dm = DocumentManager::create($this->dm->getConnection(), $this->dm->getConfiguration());

        return $dm;
    }
}

/**
 * @ODM\Document(collection="rdt_quiz_questions")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(name="type")
 * @ODM\DiscriminatorMap({"advanced"="AdvancedQuestion", "basic"="BasicQuestion"})
 */
class BasicQuestion
{
    /** @ODM\Id */
    public $id;
    /** @ODM\String */
    public $text;
    /** @ODM\ReferenceOne(targetDocument="Answer", simple=true) */
    public $answer;
}

/** @ODM\Document */
class AdvancedQuestion extends BasicQuestion
{}

/** @ODM\Document(collection="rdt_quiz") */
class Quiz
{
    /** @ODM\Id */
    public $id;
    /** @ODM\ReferenceMany(targetDocument="BasicQuestion", simple=true) */
    public $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection;
    }
}

/**
 * @ODM\Document(collection="rdt_quiz_answers")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(name="correct")
 * @ODM\DiscriminatorMap({true="CorrectAnswer", false="Answer"})
 */
class Answer
{
    /** @ODM\Id */
    public $id;
    /** @ODM\String */
    public $text;
}

/** @ODM\Document */
class CorrectAnswer extends Answer
{}