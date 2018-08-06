<?php

declare(strict_types=1);

namespace Stubs;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager as BaseDocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

class DocumentManager extends BaseDocumentManager
{
    protected $classMetadatas = [];

    private $_eventManager;

    public function __construct()
    {
        $this->_eventManager = new EventManager();
    }

    public function getEventManager(): EventManager
    {
        return $this->_eventManager;
    }

    public function setClassMetadata($className, ClassMetadata $class)
    {
        $this->classMetadatas[$className] = $class;
    }

    public function getClassMetadata($className)
    {
        if (! isset($this->classMetadatas[$className])) {
            throw new \InvalidArgumentException('Metadata for class ' . $className . ' doesn\'t exist, try calling ->setClassMetadata() first');
        }
        return $this->classMetadatas[$className];
    }
}
