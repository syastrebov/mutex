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
 * Class Test
 * @package Test
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
    private $mutex;

    /**
     * Закрывать соединение с сервисом после каждого теста
     */
    public function tearDown()
    {
        $this->mutex = null;
    }

    /**
     * Неправильно заданные параметры подключения
     *
     * @expectedException \ErlMutex\Exception\Exception
     */
    public function testInvalidConnectionParams()
    {
        $this->mutex = new Mutex('0.0.0.0', 0);
        $this->mutex
            ->setLogger(new LoggerDummy())
            ->establishConnection();
    }

    /**
     * Успешное подключение к сервису
     */
    public function testConnectionSuccess()
    {
        $this->mutex = new Mutex();
        $this->assertTrue(
            $this->mutex
                ->setProfiler(new Profiler(__FUNCTION__))
                ->establishConnection()
                ->isAlive()
        );

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Неправильно заданные параметры указателя блокировки
     *
     * @expectedException \ErlMutex\Exception\Exception
     * @dataProvider providerInvalidPointerParams
     */
    public function testInvalidPointerParams($name, $timeout)
    {
        $this->mutex = new Mutex();
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));
        $this->mutex->get($name, $timeout);
    }

    /**
     * Ошибка подключения к сервису
     */
    public function testConnectionFailure()
    {
        $this->mutex = new Mutex('127.0.0.1', 7008);
        $this->assertFalse(
            $this->mutex
                ->setLogger(new LoggerDummy())
                ->setProfiler(new Profiler(__FUNCTION__))
                ->establishConnection()
                ->isAlive()
        );

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Успешное получение блокировки
     */
    public function testGetPointerSuccess()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertEquals('A', $this->mutex->get('A'));
        $this->assertEquals('B', $this->mutex->get('B'));

        $this->mutex->release('A');
        $this->mutex->release('B');

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Получение блокировки без подключения к сервису
     */
    public function testGetPointerWithoutConnection()
    {
        $this->mutex = new Mutex();
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertNull($this->mutex->get('A'));
        $this->assertTrue($this->mutex->release('A'));

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Успешная установка блокировки
     */
    public function testAcquireSuccess()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertEquals('A', $this->mutex->get('A'));
        $this->assertTrue($this->mutex->acquire());
        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Установка блокировки без подключения к сервису
     */
    public function testAcquireWithoutConnection()
    {
        $this->mutex = new Mutex();
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertNull($this->mutex->get('A'));
        $this->assertFalse($this->mutex->acquire());
        $this->assertFalse($this->mutex->release());

        $this->assertNull($this->mutex->get('A'));
        $this->assertFalse($this->mutex->acquire());
        $this->assertFalse($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Установка блокировки без указателя
     */
    public function testAcquireWithoutPointer()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertFalse($this->mutex->acquire());
        $this->assertFalse($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Установка блокировки без указателя и подключения к сервису
     */
    public function testAcquireWithoutPointerAndConnection()
    {
        $this->mutex = new Mutex();
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertFalse($this->mutex->acquire());
        $this->assertFalse($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Попытка повторной блокировки занятой секции (тест already_acquired)
     */
    public function testAlreadyAcquired()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 500));
        $this->assertTrue($this->mutex->acquire());
        $this->assertTrue($this->mutex->acquire());
        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Попытка блокировки занятой секции (тест busy)
     */
    public function testAcquiredBusy()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 500));
        $this->assertTrue($this->mutex->acquire());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }

        unset($this->mutex);

        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 500));
        $this->assertTrue($this->mutex->acquire());
        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Протухание блокировки при снятии
     */
    public function testReleaseNotFound()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 500));
        $this->assertTrue($this->mutex->acquire());

        sleep(40);

        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Протухание блокировки при установке
     */
    public function testAcquireNotFound()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 500));

        sleep(160);

        $this->assertTrue($this->mutex->acquire());
        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Отключение сервиса в момент блокировки
     */
    public function testDisconnectWhileAcquired()
    {
        $this->mutex = new Mutex();
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A'));
        $this->assertTrue($this->mutex->acquire(), 1000);

        $this->mutex->closeConnection();

        $this->assertFalse($this->mutex->isAlive());
        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
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