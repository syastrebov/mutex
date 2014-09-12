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

use ErlMutex\Entity\Profiler\CrossOrderCollection;
use ErlMutex\Entity\Profiler\Stack;
use ErlMutex\Service\Mutex;

/**
 * Тестирование коллекции перехлестных вызовов
 *
 * Class ProfilerCrossOrderCollectionTest
 * @package ErlMutex\Test\Model
 */
class CrossOrderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Коллекция перехлестных вызовов
     *
     * @var CrossOrderCollection
     */
    private $collection;

    /**
     * Задаем новую коллекцию для каждого теста
     */
    public function setUp()
    {
        $this->collection = new CrossOrderCollection();
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
        $this->collection->getModelByTrace(new Stack(
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