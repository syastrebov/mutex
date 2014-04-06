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
 * Class DeadLockTest
 * @package ErlMutex\Test\Service
 */
class OrderTest extends \PHPUnit_Framework_TestCase
{
    const TIMEOUT = 2;

    /**
     * Конкурентный вызов в правильной последовательности
     */
    public function testSameOrder()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutexes = array();
        for ($i = 0; $i < 2; $i++) {
            $mutexes[$i] = new Mutex('127.0.0.1', 7007);

            /** @var Mutex $mutex */
            $mutex = &$mutexes[$i];
            $mutex
                ->establishConnection()
                ->setProfiler(new Profiler(__FUNCTION__ . '_' . $i))
                ->getProfiler()
                ->setStorage(ProfilerStorageDummy::getInstance());

            if ($mutex->acquire('A')) {
                sleep(self::TIMEOUT);

                if ($mutex->acquire('B')) {
                    sleep(self::TIMEOUT);
                    $mutex->release('B');
                }
                $mutex->release('A');
            }
        }

        for ($i = 0; $i < 2; $i++) {
            unset($mutexes[$i]);
        }

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(__DIR__ . ProfilerTest::OUTPUT_DIR . __CLASS__ . '/' . __FUNCTION__)
            ->generateHtmlMapOutput();
    }

    /**
     * Конкурентный вызов в неправильной последовательности
     */
    public function testWrongOrder()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutexes = array();
        for ($i = 0; $i < 2; $i++) {
            $mutexes[$i] = new Mutex('127.0.0.1', 7007);

            /** @var Mutex $mutex */
            $mutex = &$mutexes[$i];
            $mutex
                ->establishConnection()
                ->setProfiler(new Profiler(__FUNCTION__ . '_' . $i))
                ->getProfiler()
                ->setStorage(ProfilerStorageDummy::getInstance());

            if ($i > 0) {
                if ($mutex->acquire('A')) {
                    sleep(self::TIMEOUT);

                    if ($mutex->acquire('B')) {
                        sleep(self::TIMEOUT);
                        $mutex->release('B');
                    }
                    $mutex->release('A');
                }
            } else {
                if ($mutex->acquire('B')) {
                    sleep(self::TIMEOUT);

                    if ($mutex->acquire('A')) {
                        sleep(self::TIMEOUT);
                        $mutex->release('A');
                    }
                    $mutex->release('B');
                }
            }
        }

        for ($i = 0; $i < 2; $i++) {
            unset($mutexes[$i]);
        }

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(__DIR__ . ProfilerTest::OUTPUT_DIR . __CLASS__ . '/' . __FUNCTION__)
            ->generateHtmlMapOutput();
    }
} 