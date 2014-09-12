<?php

namespace ErlMutex\Test\Adapter;
use ErlMutex\Adapter\Dummy;

/**
 * Тестирование заглушки адаптера
 *
 * Class DummyTest
 * @package ErlMutex\Test\Adapter
 */
class DummyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Проверка выполнения методов
     */
    public function testMethods()
    {
        $adapter = new Dummy();
        $adapter->establishConnection();
        $adapter->get('', 0);
        $adapter->acquire('');
        $adapter->release('');
        $adapter->closeConnection();
        $adapter->isAlive();
    }
}