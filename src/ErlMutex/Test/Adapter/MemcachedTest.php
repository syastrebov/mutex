<?php

/**
 * PHP-Erlang erl
 * Сервис блокировок для обработки критических секций
 *
 * @category erl
 * @package  erl
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/mutex-php
 */

namespace ErlMutex\Test\Adapter;

use ErlMutex\Adapter\Memcached;
use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;

/**
 * Тестирование мьютекса через memcached
 *
 * Class Test
 * @package Test
 */
class MemcachedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Отображать ход выполнения
     */
    const PROFILER_DUMP_ENABLED = false;

    /**
     * Мьютекс
     *
     * @var Mutex
     */
    private $mutex;

    /**
     * Включаем мьютекс
     */
    public function setUp()
    {
        $memCached = new \Memcached();
        $memCached->addServer("127.0.0.1", 11211);

        $this->mutex = new Mutex(new Memcached($memCached));
    }

    /**
     * Закрывать соединение с сервисом после каждого теста
     */
    public function tearDown()
    {
        $this->mutex = null;
    }

    /**
     * Успешное подключение к сервису
     */
    public function testConnectionSuccess()
    {
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
     * Ошибка подключения к сервису
     */
    public function testConnectionFailure()
    {
        $this->mutex = new Mutex(new Memcached(new \Memcached()));
        $this->assertFalse(
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
     * Успешное получение блокировки
     */
    public function testGetPointerSuccess()
    {
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
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertEquals('A', $this->mutex->get('A'));
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
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertEquals('A', $this->mutex->get('A'));
        $this->assertTrue($this->mutex->acquire());
        $this->assertTrue($this->mutex->release());

        $this->assertEquals('A', $this->mutex->get('A'));
        $this->assertTrue($this->mutex->acquire());
        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Установка блокировки без указателя и подключения к сервису
     */
    public function testAcquireWithoutPointerAndConnection()
    {
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));

        $this->assertFalse($this->mutex->acquire());
        $this->assertFalse($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Попытка блокировки занятой секции (тест busy)
     */
    public function testAcquiredBusy()
    {
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 5));
        $this->assertTrue($this->mutex->acquire());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }

        $this->tearDown();
        $this->setUp();

        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 5));
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
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 1000));
        $this->assertTrue($this->mutex->acquire());

        sleep(2);

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
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 1000));
        sleep(2);

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
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 1000));
        $this->assertTrue($this->mutex->acquire());

        $this->mutex->closeConnection();

        $this->assertTrue($this->mutex->isAlive());
        $this->assertTrue($this->mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->mutex->getProfiler()->dump();
        }
    }

    /**
     * Ограничение на максимальное время ожидания
     */
    public function testMaxTimeout()
    {
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A', 20000));
        $this->assertTrue($this->mutex->acquire());
    }
}