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

use ErlMutex\Adapter\Socket;
use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;
use ErlMutex\Service\Storage\ProfilerStorageDummy;

/**
 * Тестирование профайлера
 *
 * Class ProfilerTest
 * @package Test\Service
 */
class ProfilerTest extends \PHPUnit_Framework_TestCase
{
    const OUTPUT_DIR           = '/../../../../test/profiler_output/';
    const OUTPUT_DIR_NOT_EXIST = '/../../../../test/profiler_output_not_exist/';

    /**
     * Мьютекс
     *
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
     * Отладчик
     */
    public function testGetProfiler()
    {
        $this->mutex = new Mutex(new Socket());
        $this->mutex->establishConnection();

        $this->assertNotNull($this->mutex->get('A'));
        $this->mutex->acquire();
        $this->mutex->release();

        $this->assertTrue(
            $this->mutex
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
        $this->mutex = new Mutex(new Socket());
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection();

        $this->assertNotNull($this->mutex->get('A'));
        $this->mutex->acquire();
        $this->mutex->release();

        ob_start();
        $this->assertEmpty(ob_get_clean());

        ob_start();
        $this->mutex->getProfiler()->dump();
        $this->assertNotEmpty(ob_get_clean());
    }

    /**
     * Сохранение результатов
     */
    public function testSave()
    {
        $this->mutex = new Mutex(new Socket());
        $this->mutex
            ->setProfiler(new Profiler(__FUNCTION__))
            ->establishConnection()
            ->getProfiler()
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->getStorage();

        $this->assertNotNull($this->mutex->get('A'));
        $this->mutex->acquire();
        $this->mutex->release();
    }

    /**
     * Не задано хранилище при поптыке построить карту
     * 
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testMapStorageNotSet()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler->getMap();
    }

    /**
     * Карта блокировок отладчика
     */
    public function testMap()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler->setStorage(ProfilerStorageDummy::getInstance());

        $this->assertGreaterThan(0, count($profiler->getMap()));

    }

    /**
     * Не задана директория генерации карты
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testGenerateMapDirectoryNotSet()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->generateHtmlMapOutput();
    }

    /**
     * Не задана директория генерации карты
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testInvalidGenerateMapDirectory()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(__DIR__ . self::OUTPUT_DIR_NOT_EXIST)
            ->generateHtmlMapOutput();
    }

    /**
     * Тестирование вывода карты в html виде
     */
    public function testGenerateMapHtmlOutput()
    {
        $profiler = new Profiler(__FUNCTION__);
        $profiler
            ->setStorage(ProfilerStorageDummy::getInstance())
            ->setMapOutputLocation(__DIR__ . self::OUTPUT_DIR)
            ->generateHtmlMapOutput();
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

    /**
     * Создание директории для вывода карты профайлера
     *
     * @param string $testClassName
     * @param string $testMethodName
     */
    public static function createOutputDirIfNotExist($testClassName, $testMethodName)
    {
        $testSuiteDir = self::getTestSuiteOutputDirPath($testClassName);
        $testCaseDir  = self::getTestCaseOutputDirPath($testSuiteDir, $testMethodName);

        if (!is_dir($testSuiteDir)) {
            @mkdir($testSuiteDir) or die('Не удалось создать директорию ' . $testSuiteDir);
        }
        if (!is_dir($testCaseDir)) {
            @mkdir($testCaseDir) or die('Не удалось создать директорию ' . $testCaseDir);
        }
    }

    /**
     * Путь к директории для для вывода карты профайлера для testSuite
     *
     * @param string $testClassName
     * @return string
     */
    public static function getTestSuiteOutputDirPath($testClassName)
    {
        return __DIR__ . ProfilerTest::OUTPUT_DIR . str_replace('\\', '', $testClassName);
    }

    /**
     * Путь к директории для для вывода карты профайлера для testCase
     *
     * @param string $testSuiteDirPath
     * @param string $testMethodName
     *
     * @return string
     */
    public static function getTestCaseOutputDirPath($testSuiteDirPath, $testMethodName)
    {
        return $testSuiteDirPath . '/' . $testMethodName;
    }

    /**
     * Путь к директории вывода карты профайлера
     *
     * @param string $testClassName
     * @param string $testMethodName
     *
     * @return string
     */
    public static function getMapOutputLocationPath($testClassName, $testMethodName)
    {
        $testSuiteDir = self::getTestSuiteOutputDirPath($testClassName);
        return self::getTestCaseOutputDirPath($testSuiteDir, $testMethodName);
    }
}