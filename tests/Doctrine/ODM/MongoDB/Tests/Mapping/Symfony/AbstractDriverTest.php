<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
*/

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Symfony;

/**
 * @group DDC-1418
 */
abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testFindMappingFile()
    {
        $driver = $this->getDriver(array(
            'MyNamespace\MySubnamespace\DocumentFoo' => 'foo',
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ));

        touch($filename = $this->dir.'/Foo'.$this->getFileExtension());
        $this->assertEquals($filename, $driver->getLocator()->findMappingFile('MyNamespace\MySubnamespace\Document\Foo'));
    }

    public function testFindMappingFileInSubnamespace()
    {
        $driver = $this->getDriver(array(
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ));

        touch($filename = $this->dir.'/Foo.Bar'.$this->getFileExtension());
        $this->assertEquals($filename, $driver->getLocator()->findMappingFile('MyNamespace\MySubnamespace\Document\Foo\Bar'));
    }

    public function testFindMappingFileNamespacedFoundFileNotFound()
    {
        $this->setExpectedException(
            'Doctrine\Common\Persistence\Mapping\MappingException',
            "No mapping file found named"
        );

        $driver = $this->getDriver(array(
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ));

        $driver->getLocator()->findMappingFile('MyNamespace\MySubnamespace\Document\Foo');
    }

    public function testFindMappingNamespaceNotFound()
    {
        $this->setExpectedException(
            'Doctrine\Common\Persistence\Mapping\MappingException',
            "No mapping file found named 'Foo".$this->getFileExtension()."' for class 'MyOtherNamespace\MySubnamespace\Document\Foo'."
        );

        $driver = $this->getDriver(array(
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ));

        $driver->getLocator()->findMappingFile('MyOtherNamespace\MySubnamespace\Document\Foo');
    }

    protected function setUp()
    {
        $this->dir = sys_get_temp_dir().'/abstract_driver_test';
        @mkdir($this->dir, 0775, true);
    }

    protected function tearDown()
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dir), \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($this->dir);
    }

    abstract protected function getFileExtension();
    abstract protected function getDriver(array $paths = array());

    private function setField($obj, $field, $value)
    {
        $ref = new \ReflectionProperty($obj, $field);
        $ref->setAccessible(true);
        $ref->setValue($obj, $value);
    }

    private function invoke($obj, $method, array $args = array())
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($obj, $args);
    }
}
