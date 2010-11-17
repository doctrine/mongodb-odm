<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Tests of the commit order calculation.
 *
 * IMPORTANT: When writing tests here consider that a lot of graph constellations
 * can have many valid orderings, so you may want to build a graph that has only
 * 1 valid order to simplify your tests.
 */
class CommitOrderCalculatorTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $_calc;
    
    public function setUp()
    {
        $this->calc = new \Doctrine\ODM\MongoDB\Internal\CommitOrderCalculator();
    }
    
    public function testCommitOrdering1()
    {
        $class1 = new ClassMetadata(__NAMESPACE__ . '\NodeClass1');
        $class2 = new ClassMetadata(__NAMESPACE__ . '\NodeClass2');
        $class3 = new ClassMetadata(__NAMESPACE__ . '\NodeClass3');
        $class4 = new ClassMetadata(__NAMESPACE__ . '\NodeClass4');
        $class5 = new ClassMetadata(__NAMESPACE__ . '\NodeClass5');
        
        $this->calc->addClass($class1);
        $this->calc->addClass($class2);
        $this->calc->addClass($class3);
        $this->calc->addClass($class4);
        $this->calc->addClass($class5);
        
        $this->calc->addDependency($class1, $class2);
        $this->calc->addDependency($class2, $class3);
        $this->calc->addDependency($class3, $class4);
        $this->calc->addDependency($class5, $class1);

        $sorted = $this->calc->getCommitOrder();
        
        // There is only 1 valid ordering for this constellation
        $correctOrder = array($class5, $class1, $class2, $class3, $class4);
        $this->assertSame($correctOrder, $sorted);
    }
}

class NodeClass1 {}
class NodeClass2 {}
class NodeClass3 {}
class NodeClass4 {}
class NodeClass5 {}