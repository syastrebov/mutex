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

namespace ErlMutex\Test\Model;

use ErlMutex\Model\ProfilerCrossOrderCollection;
use ErlMutex\Model\ProfilerStack;
use ErlMutex\Service\Mutex;

/**
 * Тестирование коллекции перехлестных вызовов
 *
 * Class ProfilerCrossOrderCollectionTest
 * @package ErlMutex\Test\Model
 */
class ProfilerCrossOrderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Коллекция перехлестных вызовов
     *
     * @var ProfilerCrossOrderCollection
     */
    private $collection;

    /**
     * Задаем новую коллекцию для каждого теста
     */
    public function setUp()
    {
        $this->collection = new ProfilerCrossOrderCollection();
    }

    /**
     * Удаляем коллекцию после каждого теста
     */
    public function tearDown()
    {
        $this->collection = null;
    }

    /**
     * Получение несуществующей блокировки
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testGetModelByTraceNotFound()
    {
        $this->collection->getModelByTrace(new ProfilerStack(
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
        ));
    }
}