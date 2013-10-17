<?php

namespace Doctrine\ODM\MongoDB\Tests\Performance;

use Doctrine\ODM\MongoDB\Tests\BaseTest;

class PerformanceTest extends BaseTest
{
    /**
     * @var integer
     */
    protected $maxRunningTime = 0;

    /**
     * @return void
     */
    protected function runTest()
    {
        $s = microtime(true);
        parent::runTest();
        $time = microtime(true) - $s;

        if ($this->maxRunningTime != 0 && $time > $this->maxRunningTime) {
            $this->fail(
              sprintf(
                'expected running time: <= %s but was: %s',
                $this->maxRunningTime,
                $time
              )
            );
        }
    }

    /**
     * @param integer $maxRunningTime
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function setMaxRunningTime($maxRunningTime)
    {
        if (is_integer($maxRunningTime) && $maxRunningTime >= 0) {
            $this->maxRunningTime = $maxRunningTime;
        } else {
            throw new \InvalidArgumentException;
        }
    }

    /**
     * @return integer
     */
    public function getMaxRunningTime()
    {
        return $this->maxRunningTime;
    }
}
