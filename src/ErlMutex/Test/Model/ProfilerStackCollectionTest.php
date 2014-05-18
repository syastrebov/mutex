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

use ErlMutex\Model\ProfilerStackCollection;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use DateTime;

/**
 * Тестирование коллекции запросов в пределах одной сессии
 *
 * Class ProfilerStackCollectionTest
 * @package ErlMutex\Test\Model
 */
class ProfilerStackCollectionTest extends \PHPUnit_Framework_TestCase
{
    const REQUEST_1 = 'request_1';
    const REQUEST_2 = 'request_2';

    /**
     * Коллекция запросов в пределах одной сессии
     *
     * @var ProfilerStackCollection
     */
    private $collection;

    /**
     * Задаем новую коллекцию для каждого теста
     */
    public function setUp()
    {
        $this->collection = new ProfilerStackCollection(md5(self::REQUEST_1));
    }

    /**
     * Удаляем коллекцию после каждого теста
     */
    public function tearDown()
    {
        $this->collection = null;
    }

    /**
     * Тестирование успешного добавления запроса
     */
    public function testSuccessAppend()
    {
        $this->collection->append(new ProfilerStackModel(
            self::REQUEST_1,
            md5(self::REQUEST_1),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_1',
            'response_1',
            new DateTime()
        ));
    }

    /**
     * Тестирование добавления запроса с неправильным хешом
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testFailureAppend()
    {
        $this->collection->append(new ProfilerStackModel(
            self::REQUEST_2,
            md5(self::REQUEST_2),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_1',
            'response_1',
            new DateTime()
        ));
    }

    /**
     * Получить хеш запроса коллекции
     */
    public function testRequestGetHash()
    {
        $this->assertEquals(md5(self::REQUEST_1), $this->collection->getRequestHash());
    }

    /**
     * Получить хеш коллекции
     */
    public function testGetModelHash()
    {
        $this->assertEquals('d41d8cd98f00b204e9800998ecf8427e', $this->collection->getModelHash());

        $this->collection->append(new ProfilerStackModel(
            self::REQUEST_1,
            md5(self::REQUEST_1),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_1',
            'response_1',
            new DateTime()
        ));

        $this->assertEquals('e4307a64ba75a29b0774c378e1260be4', $this->collection->getModelHash());
    }

    /**
     * Получение количества моделей в коллекции
     */
    public function testGetCount()
    {
        $this->collection->append(new ProfilerStackModel(
            self::REQUEST_1,
            md5(self::REQUEST_1),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_1',
            'response_1',
            new DateTime()
        ));

        $this->assertEquals(1, count($this->collection));
    }

    /**
     * Тестирование чтения коллекции через foreach()
     */
    public function testIterate()
    {
        $this->collection->append(new ProfilerStackModel(
            self::REQUEST_1,
            md5(self::REQUEST_1),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_1',
            'response_1',
            new DateTime()
        ));

        $this->collection->append(new ProfilerStackModel(
            self::REQUEST_1,
            md5(self::REQUEST_1),
            'file_1',
            1,
            'class_1',
            'method_1',
            'key_1',
            'action_2',
            'response_2',
            new DateTime()
        ));

        $counter = 0;
        foreach ($this->collection as $trace) {
            $counter++;
            $this->assertInstanceOf('\ErlMutex\Model\ProfilerStack', $trace);
        }

        $this->assertEquals(2, $counter);

        $counter = 0;
        foreach ($this->collection as $trace) {
            $counter++;
            $this->assertInstanceOf('\ErlMutex\Model\ProfilerStack', $trace);
        }

        $this->assertEquals(2, $counter);
    }

    /**
     * Попытка получения uri запроса из пустой коллекции
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testGetRequestUriFromEmptyCollection()
    {
        $this->collection->getRequestUri();
    }
}