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

use ErlMutex\Adapter\Dummy;
use ErlMutex\Service\Mutex;
use ErlMutex\Service\Profiler;

/**
 * Тестирование мьютекса
 *
 * Class MutexTest
 * @package ErlMutex\Test\Service
 */
class MutexTest extends \PHPUnit_Framework_TestCase
{
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
     * Неправильно заданные параметры указателя блокировки
     *
     * @expectedException \ErlMutex\Exception\Exception
     * @dataProvider providerInvalidPointerParams
     */
    public function testInvalidPointerParams($name, $timeout)
    {
        $this->mutex = new Mutex(new Dummy());
        $this->mutex->setProfiler(new Profiler(__FUNCTION__));
        $this->mutex->get($name, $timeout);
    }

    /**
     * Неправильно заданные параметры указателя блокировки
     *
     * @return array
     */
    public function providerInvalidPointerParams()
    {
        return [
            [null,  false],
            [1.2,   false],
            [false, false],
            ['A',   -1],
            ['A',   1.2],
            ['A',   true],
            ['A',   'A'],
        ];
    }
}