<?php

/**
 * PHP-Erlang erl
 * Сервис блокировок для обработки критических секций
 *
 * @category erl
 * @package  erl
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/erl
 */

namespace ErlMutex\Test\Service\Storage;

use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;
use ErlMutex\Service\Storage\ProfilerStorageDummy;

/**
 * Class ProfilerStorageDummyTest
 * @package Test\Service\Storage
 */
class ProfilerStorageDummyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mutex
     */
    private $_mutex;

    /**
     * Закрывать соединение с сервисом после каждого теста
     */
    public function tearDown()
    {
        $this->_mutex = null;
    }

    /**
     * Запрещаем unserialize
     *
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testUnserialize()
    {
        $storage = ProfilerStorageDummy::getInstance();
        unserialize(serialize($storage));
    }

    /**
     * Очистка хранилища
     */
    public function testTruncate()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $this->_mutex->get('A');
        $this->_mutex->acquire();
        $this->_mutex->release();

        $storage = ProfilerStorageDummy::getInstance();
        $this->assertGreaterThan(0, count($storage->getList()));

        $storage->truncate();
        $this->assertEquals(0, count($storage->getList()));
    }
}