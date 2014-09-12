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

namespace ErlMutex\Test\Entity\Profiler;

use ErlMutex\Entity\Profiler\CrossOrder;

/**
 * Тестирование модели перехлестных вызовов
 *
 * Class ProfilerCrossOrderTest
 * @package ErlMutex\Test\Model
 */
class CrossOrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Модель перехлестных вызовов
     *
     * @var CrossOrder
     */
    private $profilerCrossOrderModel;

    /**
     * Задаем новую модель для каждого теста
     */
    public function setUp()
    {
        $this->profilerCrossOrderModel = new CrossOrder(__CLASS__);
    }

    /**
     * Удаляем модель после каждого теста
     */
    public function tearDown()
    {
        $this->profilerCrossOrderModel = null;
    }

    /**
     * Повторная блокировка модели
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testAlreadyAcquired()
    {
        $this->profilerCrossOrderModel->acquire();
        $this->profilerCrossOrderModel->acquire();
    }

    /**
     * Разблокировка незаблокированной модели
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testReleaseNotAcquired()
    {
        $this->profilerCrossOrderModel->release();
    }

    /**
     * Повторное добавление ключа
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testContainsKeyAlreadyExists()
    {
        $this->profilerCrossOrderModel->addContainKey('A');
        $this->profilerCrossOrderModel->addContainKey('A');
    }
}