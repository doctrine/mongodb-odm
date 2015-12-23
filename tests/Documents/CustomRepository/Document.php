<?php

namespace Documents\CustomRepository;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(repositoryClass="Documents\CustomRepository\Repository")
 */
class Document {
	/**
	 * @ODM\Id
	 */
	protected $id;
}
