<?php

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;

class GridFS extends \Doctrine\MongoDB\GridFS
{
    private $dm;
    private $class;

    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function setClassMetadata(ClassMetadata $class)
    {
        $this->class = $class;
    }

    /** @override */
    public function find(array $query = array(), array $fields = array())
    {
        if ($this->dm && $this->class) {
            $query = $this->dm->prepareQuery($this->class, $query);
        }
        return parent::find($query, $fields);
    }

    /** @override */
    public function findOne(array $query = array(), array $fields = array())
    {
        if ($this->dm && $this->class) {
            $query = $this->dm->prepareQuery($this->class, $query);
        }
        return parent::findOne($query, $fields);
    }
}