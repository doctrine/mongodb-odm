<?php

namespace Documents\CustomRepository;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */

/**
 * @ODM\Document(repositoryClass="Documents\CustomRepository\Repository")
 */
class Document {
	/**
	 * @ODM\Id
	 */
	protected $id;
}