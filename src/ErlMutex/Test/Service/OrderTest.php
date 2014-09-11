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

namespace ErlMutex\Test\Service;

use ErlMutex\Model\ProfilerMapCollection;
use ErlMutex\Model\ProfilerStack;
use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;
use ErlMutex\Service\Storage\ProfilerStorageDummy;

/**
 * Тестирование профайлера (очередь вызова блокировок)
 *
 * Class DeadLockTest
 * @package ErlMutex\Test\Service
 */
class OrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Конкурентный вызов в правильной последовательности
     */
    public function testSameOrder()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutexes = array();
        for ($i = 0; $i < 2; $i++) {
            $mutexes[$i] = new Mutex();

            /** @var Mutex $mutex */
            $mutex = &$mutexes[$i];
            $mutex
                ->establishConnection()
                ->setProfiler(new Profiler(__FUNCTION__ . '_' . $i))
                ->getProfiler()
                ->setStorage(ProfilerStorageDummy::getInstance());

            $mutex->get('A');
            $mutex->get('B');
            $mutex->get('C');

            if ($i > 0) {
                if ($mutex->acquire('A')) {
                    if ($mutex->acquire('B')) {
                        if ($mutex->acquire('C')) {
                            $mutex->release('C');
                        }
                        $mutex->release('B');
                    }
                    $mutex->release('A');
                }
            } else {
                if ($mutex->acquire('A')) {
                    if ($mutex->acquire('B')) {
                        $mutex->release('B');
                    }
                    $mutex->release('A');
                }
            }
        }

        for ($i = 0; $i < 2; $i++) {
            unset($mutexes[$i]);
        }

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
            ->generateHtmlMapOutput();
    }

    /**
     * Без вложенности
     */
    public function testEmptyContains()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutex = new Mutex();
        $mutex
            ->establishConnection()
            ->setProfiler(new Profiler(__FUNCTION__))
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $mutex->get('A');
        $mutex->get('B');

        if ($mutex->acquire('A')) {
            $mutex->release('A');
        }
        if ($mutex->acquire('B')) {
            $mutex->release('B');
        }

        unset($mutex);

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
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
            $mutexes[$i] = new Mutex();

            /** @var Mutex $mutex */
            $mutex = &$mutexes[$i];
            $mutex
                ->establishConnection()
                ->setProfiler(new Profiler(__FUNCTION__ . '_' . $i))
                ->getProfiler()
                ->setStorage(ProfilerStorageDummy::getInstance());

            $mutex->get('A');
            $mutex->get('B');

            if ($i > 0) {
                if ($mutex->acquire('A')) {
                    if ($mutex->acquire('B')) {
                        $mutex->release('B');
                    }
                    $mutex->release('A');
                }
            } else {
                if ($mutex->acquire('B')) {
                    if ($mutex->acquire('A')) {
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
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
            ->generateHtmlMapOutput();
    }

    /**
     * Перехлестный вызов
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testCrossOrder()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutex = new Mutex();
        $mutex
            ->establishConnection()
            ->setProfiler(new Profiler(__FUNCTION__))
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $mutex->get('A');
        $mutex->get('B');

        if ($mutex->acquire('A')) {
            if ($mutex->acquire('B')) {
                $mutex->release('A');
            }
            $mutex->release('B');
        }

        unset($mutex);

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
            ->generateHtmlMapOutput();

        $map = $profiler->getMap();

        $this->assertEquals(1, count($map));
        $profiler->validate($map);
    }

    /**
     * Уже был получен указатель на блокировку
     */
    public function testAlreadyHasPointer()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutex = new Mutex();
        $mutex
            ->establishConnection()
            ->setProfiler(new Profiler(__FUNCTION__))
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $mutex->get('A');
        $mutex->get('A');
        $mutex->acquire();
        $mutex->release();

        unset($mutex);

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
            ->generateHtmlMapOutput();
    }

    /**
     * Попытка заблокировать без указателя
     */
    public function testAcquireWithoutPointer()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutex = new Mutex();
        $mutex
            ->establishConnection()
            ->setProfiler(new Profiler(__FUNCTION__))
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $mutex->acquire();
        $mutex->release();

        unset($mutex);

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
            ->generateHtmlMapOutput();
    }

    /**
     * Попытка повторной блокировки
     */
    public function testAlreadyAcquired()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutex = new Mutex();
        $mutex
            ->establishConnection()
            ->setProfiler(new Profiler(__FUNCTION__))
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $mutex->get('A');
        $mutex->acquire('A');
        $mutex->acquire('A');
        $mutex->release();

        unset($mutex);

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
            ->generateHtmlMapOutput();
    }

    /**
     * Снять незанятую блокировку
     */
    public function testReleaseNotAcquired()
    {
        ProfilerTest::createOutputDirIfNotExist(__CLASS__, __FUNCTION__);
        ProfilerStorageDummy::getInstance()->truncate();

        $mutex = new Mutex();
        $mutex
            ->establishConnection()
            ->setProfiler(new Profiler(__FUNCTION__))
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance());

        $mutex->get('A');
        $mutex->release();

        unset($mutex);

        $profiler = new Profiler('');
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(ProfilerTest::getMapOutputLocationPath(__CLASS__, __FUNCTION__))
            ->generateHtmlMapOutput();
    }

    /**
     * Неправильный хеш при проверки списка по ключу и хешу
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testValidateKeyHashActionsOrderWrongHashList()
    {
        $collection = new ProfilerMapCollection(md5(__FUNCTION__));
        $collection
            ->append(new ProfilerStack(
                __FUNCTION__,
                md5(__FUNCTION__),
                __LINE__,
                __FILE__,
                __CLASS__,
                __FUNCTION__,
                'A',
                Mutex::ACTION_GET,
                '',
                new \DateTime()
            ))
            ->append(new ProfilerStack(
                __FUNCTION__,
                md5(__CLASS__ . __FUNCTION__),
                __FILE__,
                __LINE__,
                __CLASS__,
                __FUNCTION__,
                'A',
                Mutex::ACTION_ACQUIRE,
                '',
                new \DateTime()
            ));

        $profiler = new Profiler(__FUNCTION__);
        $profiler->validate($collection);
    }
} 