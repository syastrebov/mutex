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

namespace ErlMutex\Test\Service;

use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;
use ErlMutex\Service\Storage\ProfilerStorageDummy;

/**
 * Class ProfilerTest
 * @package Test\Service
 */
class ProfilerTest extends \PHPUnit_Framework_TestCase
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
     * @expectedException \ErlMutex\Exception\ProfilerException
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
     * @expectedException \ErlMutex\Exception\ProfilerException
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

        var_dump($profiler->map());

    }

    public function testGenerateMapHtmlOutput()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(__DIR__ . '/../../../../test/profiler_output/')
            ->generateMapHtmlOutput();
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