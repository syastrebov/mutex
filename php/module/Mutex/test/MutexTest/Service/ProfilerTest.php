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

namespace MutexTest\Service;

use Mutex\Service\Mutex;
use Mutex\Service\Profiler;
use Mutex\Service\Storage\ProfilerStorageDummy;

/**
 * Class ProfilerTest
 * @package MutexTest\Service
 */
class ProfilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Mutex
     */
    private $_mutex;

    /**
     *
     */
    public function tearDown()
    {
        $this->_mutex = null;
    }

    /**
     * Отладчик
     */
    public function testGetProfiler()
    {
        $this->_mutex = new Mutex();
        $this->_mutex->establishConnection();

        $this->assertNotNull($this->_mutex->get('A'));
        $this->_mutex->acquire();
        $this->_mutex->release();

        $this->assertTrue(
            $this->_mutex
                ->setProfiler(new Profiler(__FUNCTION__))
                ->getProfiler()
            instanceof Profiler
        );
    }

    /**
     * Неправильно заданная точка входа
     *
     * @expectedException \Mutex\Exception\ProfilerException
     * @dataProvider providerInvalidRequestUri
     */
    public function testInvalidRequestUri($requestUri)
    {
        new Profiler($requestUri);
    }

    /**
     * Показать ход выполнения
     */
    public function testDump()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->_mutex->get('A'));
        $this->_mutex->acquire();
        $this->_mutex->release();

        $this->_mutex->getProfiler()->dump();
    }

    /**
     * Сохранение результатов
     */
    public function testSave()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection()
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $this->assertNotNull($this->_mutex->get('A'));
        $this->_mutex->acquire();
        $this->_mutex->release();
    }

    /**
     * Не задано хранилище при поптыке построить карту
     * 
     * @expectedException \Mutex\Exception\ProfilerException
     */
    public function testMapStorageNotSet()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler->map();
    }

    /**
     * Карта блокировок отладчика
     */
    public function testMap()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler->setStorage(ProfilerStorageDummy::getInstance());
        $profiler->map();
    }

    /**
     * Неправильно заданная точка входа
     *
     * @return array
     */
    public function providerInvalidRequestUri()
    {
        return array(
            array(1),
            array(null),
            array(false),
            array(array()),
            array(1.2),
            array(new \stdClass())
        );
    }
}