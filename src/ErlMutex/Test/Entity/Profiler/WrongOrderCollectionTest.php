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

use ErlMutex\Entity\Profiler\Stack;
use ErlMutex\Entity\Profiler\WrongOrderCollection;
use ErlMutex\Service\Mutex;

/**
 * Тестирование коллекции правильной последовательности вызова ключей
 *
 * Class ProfilerWrongOrderCollectionTest
 * @package ErlMutex\Test\Model
 */
class WrongOrderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Коллекция правильной последовательности вызова ключей
     *
     * @var WrongOrderCollection
     */
    private $collection;

    /**
     * Задаем новую коллекцию для каждого теста
     */
    public function setUp()
    {
        $this->collection = new WrongOrderCollection();
    }

    /**
     * Удаляем коллекцию после каждого теста
     */
    public function tearDown()
    {
        $this->collection = null;
    }

    /**
     * Попытка получить несуществующую модель
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