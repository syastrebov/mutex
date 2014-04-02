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

use ErlMutex\Service\Logger\LoggerDummy;
use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;

/**
 * Class ErlMutexTest
 * @package ErlMutexTest
 */
class MutexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Отображать ход выполнения
     */
    const PROFILER_DUMP_ENABLED = true;

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
     * Неправильно заданные параметры подключения
     *
     * @expectedException \Mutex\Exception\Exception
     */
    public function testInvalidConnectionParams()
    {
        $this->_mutex = new Mutex('0.0.0.0', 0);
        $this->_mutex
            ->setLogger(new LoggerDummy())
            ->establishConnection();
    }

    /**
     * Успешное подключение к сервису
     */
    public function testConnectionSuccess()
    {
        $this->_mutex = new Mutex();
        $this->assertTrue(
            $this->_mutex
                ->setProfiler(new Profiler(__FUNCTION__))
                ->establishConnection()
                ->isAlive()
        );

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Неправильно заданные параметры указателя блокировки
     *
     * @expectedException \Mutex\Exception\Exception
     * @dataProvider providerInvalidPointerParams
     */
    public function testInvalidPointerParams($name, $timeout)
    {
        $this->_mutex = new Mutex();
        $this->_mutex->setProfiler(new Profiler(__FUNCTION__));
        $this->_mutex->get($name, $timeout);
    }

    /**
     * Ошибка подключения к сервису
     */
    public function testConnectionFailure()
    {
        $this->_mutex = new Mutex('127.0.0.1', 7008);
        $this->assertFalse(
            $this->_mutex
                ->setLogger(new LoggerDummy())
                ->setProfiler(new Profiler(__FUNCTION__))
                ->establishConnection()
                ->isAlive()
        );

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Успешное получение блокировки
     */
    public function testGetPointerSuccess()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertEquals('A', $this->_mutex->get('A'));
        $this->assertEquals('B', $this->_mutex->get('B'));

        $this->_mutex->release('A');
        $this->_mutex->release('B');

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Получение блокировки без подключения к сервису
     */
    public function testGetPointerWithoutConnection()
    {
        $this->_mutex = new Mutex();
        $this->_mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertNull($this->_mutex->get('A'));
        $this->assertTrue($this->_mutex->release('A'));

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Успешная установка блокировки
     */
    public function testAcquireSuccess()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertEquals('A', $this->_mutex->get('A'));
        $this->assertTrue($this->_mutex->acquire());
        $this->assertTrue($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Установка блокировки без подключения к сервису
     */
    public function testAcquireWithoutConnection()
    {
        $this->_mutex = new Mutex();
        $this->_mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertNull($this->_mutex->get('A'));
        $this->assertFalse($this->_mutex->acquire());
        $this->assertFalse($this->_mutex->release());

        $this->assertNull($this->_mutex->get('A'));
        $this->assertFalse($this->_mutex->acquire());
        $this->assertFalse($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Установка блокировки без указателя
     */
    public function testAcquireWithoutPointer()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertFalse($this->_mutex->acquire());
        $this->assertFalse($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Установка блокировки без указателя и подключения к сервису
     */
    public function testAcquireWithoutPointerAndConnection()
    {
        $this->_mutex = new Mutex();
        $this->_mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertFalse($this->_mutex->acquire());
        $this->assertFalse($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Попытка повторной блокировки занятой секции (тест already_acquired)
     */
    public function testAlreadyAcquired()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->_mutex->get('A', 500));
        $this->assertTrue($this->_mutex->acquire());
        $this->assertTrue($this->_mutex->acquire());
        $this->assertTrue($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Попытка блокировки занятой секции (тест busy)
     */
    public function testAcquiredBusy()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->_mutex->get('A', 500));
        $this->assertTrue($this->_mutex->acquire());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }

        unset($this->_mutex);

        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->_mutex->get('A', 500));
        $this->assertTrue($this->_mutex->acquire());
        $this->assertTrue($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Протухание блокировки при снятии
     */
    public function testReleaseNotFound()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->_mutex->get('A', 500));
        $this->assertTrue($this->_mutex->acquire());

        sleep(40);

        $this->assertTrue($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Протухание блокировки при установке
     */
    public function testAcquireNotFound()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->_mutex->get('A', 500));

        sleep(160);

        $this->assertTrue($this->_mutex->acquire());
        $this->assertTrue($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Отключение сервиса в момент блокировки
     */
    public function testDisconnectWhileAcquired()
    {
        $this->_mutex = new Mutex();
        $this->_mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->_mutex->get('A'));
        $this->assertTrue($this->_mutex->acquire(), 1000);

        $this->_mutex->closeConnection();

        $this->assertFalse($this->_mutex->isAlive());
        $this->assertTrue($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    /**
     * Неправильно заданные параметры указателя блокировки
     *
     * @return array
     */
    public function providerInvalidPointerParams()
    {
        return array(
            array(null, false),
            array(1.2, false),
            array(false, false),
            array('A', -1),
            array('A', 1.2),
            array('A', true),
            array('A', 'A'),
        );
    }
}