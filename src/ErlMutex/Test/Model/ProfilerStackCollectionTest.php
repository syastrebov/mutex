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
 * Class ProfilerStackCollectionTest
 * @package ErlMutex\Test\Model
 */
class ProfilerStackCollectionTest extends \PHPUnit_Framework_TestCase
{
    const REQUEST_1 = 'request_1';
    const REQUEST_2 = 'request_2';

    /**
     * @var ProfilerStackCollection
     */
    private $collection;

    /**
     *
     */
    public function setUp()
    {
        $this->collection = new ProfilerStackCollection(md5(self::REQUEST_1));
    }

    /**
     *
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
     *
     * @after testSuccessAppend
     */
    public function testGetModelHash()
    {
        $this->assertEquals('d41d8cd98f00b204e9800998ecf8427e', $this->collection->getModelHash());
    }

    /**
     * Сравнение двух коллекций
     */
    public function testCompareCollections()
    {

    }

    /**
     * Тестирование чтения коллекции через foreach()
     *
     * @after testSuccessAppend
     */
    public function testIterate()
    {
        foreach ($this->collection as $trace) {
            var_dump($trace);
        }
    }
}