<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mocks;

use Doctrine\ODM\MongoDB\DocumentManager;
use ReflectionClass;
use ReflectionException;

class DocumentManagerMock extends DocumentManager
{
    private $reflectionClass;

    public function __construct()
    {
        $this->reflectionClass = new ReflectionClass(parent::class);
    }

    /**
     * Set private properties declared in the DocumentManager class.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws ReflectionException If the property does not exist.
     */
    public function __set($name, $value)
    {
        $property = $this->reflectionClass->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this, $value);
    }

    /**
     * Get private properties declared in the DocumentManager class.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws ReflectionException If the property does not exist.
     */
    public function __get($name)
    {
        $property = $this->reflectionClass->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
