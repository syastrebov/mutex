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

namespace ErlMutex\Test\Model;
use ErlMutex\Model\ProfilerCrossOrder;

/**
 * Class ProfilerCrossOrderTest
 * @package ErlMutex\Test\Model
 */
class ProfilerCrossOrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProfilerCrossOrder
     */
    private $_profilerCrossOrderModel;

    /**
     *
     */
    public function setUp()
    {
        $this->_profilerCrossOrderModel = new ProfilerCrossOrder(__CLASS__);
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->_profilerCrossOrderModel = null;
    }

    /**
     * Повторная блокировка модели
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testAlreadyAcquired()
    {
        $this->_profilerCrossOrderModel->acquire();
        $this->_profilerCrossOrderModel->acquire();
    }

    /**
     * Разблокировка незаблокированной модели
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testReleaseNotAcquired()
    {
        $this->_profilerCrossOrderModel->release();
    }

    /**
     * Повторное добавление ключа
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testContainsKeyAlreadyExists()
    {
        $this->_profilerCrossOrderModel->addContainsKey('A');
        $this->_profilerCrossOrderModel->addContainsKey('A');
    }
}