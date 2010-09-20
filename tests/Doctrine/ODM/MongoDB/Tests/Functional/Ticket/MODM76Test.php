<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

use Doctrine\Common\Collections\ArrayCollection;

class MODM76Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest {
	
	public function testTest() {
		$c1 = new C;
		$c2 = new C;
		$b = new B(array($c1, $c2));
		$a = new A(array($b), array($c1));
		$this->dm->persist($a);
		$this->dm->flush();

		$this->assertTrue($a->getId() != null);
	}
}

/** @Document(db="tests", collection="tests") */
class A {
	/** @Id */
	protected $id;
	/** @EmbedMany(targetDocument="b") */
	protected $b = array();
	/** @ReferenceMany(targetDocument="c") */
	protected $c = array();

	public function __construct($b, $c) {
		$this->b = new ArrayCollection($b);
		$this->c = new ArrayCollection($c);
	}

	public function getB() {
		return $this->b;
	}

	public function getC() {
		return $this->c;
	}

	public function getId() {
		return $this->id;
	}
}

/** @EmbeddedDocument */
class B {
	/** @ReferenceOne(targetDocument="c") */
	protected $c;

	public function __construct($c) {
		$this->c = $c;
	}

	public function getC() {
		return $this->c;
	}
}
/** @Document(db="tests", collection="tests2") */
class C {
	/** @Id */
	protected $id;
}