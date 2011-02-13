<?php

namespace Documents\CustomRepository;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */

/**
 * @Document(collection="accounts", repositoryClass="Documents\CustomRepository\Repository")
 */
class Document {
	/**
	 * @Id
	 */
	protected $id;
}