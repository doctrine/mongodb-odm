<?php

namespace Documents\CustomRepository;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */

/**
 * @Document(db="doctrine_odm_tests", collection="accounts", repositoryClass="Documents\CustomRepository\Repository")
 */
class Document {
	/**
	 * @Id
	 */
	protected $id;
}