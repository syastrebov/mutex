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

namespace MutexTest;
use Mutex\Service\Logger\LoggerDummy;
use Mutex\Service\Mutex;
use Mutex\Service\Profiler;

/**
 * Class MutexTest
 * @package MutexTest
 */
class MutexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Отображать ход выполнения
     */
    const PROFILER_DUMP_ENABLED = false;

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
        $this->_mutex->release('A');

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
     * Попытка блокировки занятой секции (тест busy)
     */
    public function testAcquireBusy()
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

        sleep(140);

        $this->assertTrue($this->_mutex->acquire());
        $this->assertTrue($this->_mutex->release());

        if (self::PROFILER_DUMP_ENABLED) {
            $this->_mutex->getProfiler()->dump();
        }
    }

    public function testDisconnectWhileAcquired()
    {
//        $this->_mutex = new Mutex();
//        $this->_mutex
//            ->setProfiler(new Profiler(__FUNCTION__))
//            ->establishConnection();
//
//        $this->assertNotNull($this->_mutex->get('A', 500));
//        if (self::PROFILER_DUMP_ENABLED) {
//            $this->_mutex->getProfiler()->dump();
//        }
    }

    /**
     * Отладчик
     */
    public function testGetProfiler()
    {
        $this->_mutex = new Mutex();
        $this->assertTrue(
            $this->_mutex
                ->setProfiler(new Profiler(__FUNCTION__))
                ->getProfiler()
            instanceof Profiler
        );
    }

    /**
     * Неправильно заданные параметры указателя блокировки
     *
     * @return array
     */
    public function providerInvalidPointerParams()
    {
        return array(
            array(1.2, false),
            array(false, false),
            array('A', -1),
            array('A', 1.2),
            array('A', true),
            array('A', 'A'),
        );
    }
}