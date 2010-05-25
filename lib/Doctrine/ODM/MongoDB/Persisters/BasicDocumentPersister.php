<?php

namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager,
	Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
	\Doctrine\ODM\MongoDB\MongoCursor;

/**
 * @author Bulat Shakirzyanov <bulat@theopenskyproject.com>
 */
class BasicDocumentPersister
{
	protected $_dm;
	protected $_uow;
	protected $_class;
	protected $_collection;
	public function __construct(DocumentManager $dm, ClassMetadata $class)
	{
		$this->_dm = $dm;
		$this->_uow = $dm->getUnitOfWork();
		$this->_class = $class;
        $this->_collection = $dm->getDocumentCollection($class->name);
	}
    public function addInsert($document)
	{

	}
    public function executeInserts()
	{

	}
    public function update($document)
	{

	}
    public function delete($document)
	{

	}

    /**
     * Gets the ClassMetadata instance of the entity class this persister is used for.
     *
     * @return Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->_class;
    }
    public function refresh(array $id, $document)
	{
		
	}

    /**
     * Loads an entity by a list of field criteria.
     *
     * @param array $query The criteria by which to load the entity.
     * @param object $document The entity to load the data into. If not specified,
     *        a new entity is created.
     * @param $assoc The association that connects the entity to load to another entity, if any.
     * @param array $hints Hints for entity creation.
     * @return object The loaded and managed entity instance or NULL if the entity can not be found.
     * @todo Check identity map? loadById method? Try to guess whether $criteria is the id?
     */
    public function load(array $query = array(), array $select = array())
    {
        $result = $this->_collection->findOne($query, $select);
        if ($result !== null) {
            return $this->_unitOfWork->getOrCreateDocument($this->_documentName, $result);
        }
        return null;
    }

    public function loadById($id)
	{
		$result = $this->_collection->findOne(array('_id' => new \MongoId($query)));
		if ($result !== null) {
			return $this->_uow->getOrCreateDocument($this->_documentName, $result);
		}
		return null;
	}

    /**
     * Loads a list of entities by a list of field criteria.
     *
     * @param array $criteria
     * @return array
     */
    public function loadAll(array $query = array(), array $select = array())
	{
		$cursor = $this->_collection->find($query, $select);
		return new MongoCursor($this->_dm, $this->_hydrator, $this->_class, $cursor);
	}
}
