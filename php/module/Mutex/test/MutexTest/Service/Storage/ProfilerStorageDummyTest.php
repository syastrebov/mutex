<?php

/**
 * PHP-Erlang Mutex
 * Сервис блокировок для обработки критических секций
 *
 * @category Mutex
 * @package  Mutex
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/mutex
 */

namespace MutexTest\Service\Storage;
use Mutex\Service\Mutex;
use Mutex\Service\Profiler;
use Mutex\Service\Storage\ProfilerStorageDummy;

/**
 * Class ProfilerStorageDummyTest
 * @package MutexTest\Service\Storage
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