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

use ErlMutex\Entity\Profiler\MapCollection;
use ErlMutex\Entity\Profiler\Stack;
use ErlMutex\Service\Mutex;
use DateTime;

/**
 * Тестирование карты профайлера
 *
 * Class ProfilerMapCollectionTest
 * @package ErlMutex\Test\Model
 */
class MapCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Коллекция карты профайлера
     *
     * @var MapCollection
     */
    private $collection;

    /**
     * Задаем новую коллекцию для каждого теста
     */
    public function setUp()
    {
        $this->collection = new MapCollection();
    }

    /**
     * Удаляем коллекцию после каждого теста
     */
    public function tearDown()
    {
        $this->collection = null;
    }

    /**
     * Тестирование добавления запросов
     */
    public function testAppend()
    {
        $this->collection->append(new Stack(
            __FUNCTION__,
            md5(__FUNCTION__ . 1),
            __FILE__,
            1,
            __CLASS__,
            __FUNCTION__,
            'A',
            Mutex::ACTION_GET,
            '',
            new DateTime()
        ));

        $this->assertEquals(1, count($this->collection));

        $this->collection->append(new Stack(
            __FUNCTION__,
            md5(__FUNCTION__ . 1),
            __FILE__,
            1,
            __CLASS__,
            __FUNCTION__,
            'A',
            Mutex::ACTION_ACQUIRE,
            '',
            new DateTime()
        ));

        $this->assertEquals(1, count($this->collection));

        $this->collection->append(new Stack(
            __FUNCTION__,
            md5(__FUNCTION__ . 2),
            __FILE__,
            1,
            __CLASS__,
            __FUNCTION__,
            'A',
            Mutex::ACTION_GET,
            '',
            new DateTime()
        ));

        $this->assertEquals(2, count($this->collection));
    }

    /**
     * Проверка наличия коллекции
     */
    public function testHasCollection()
    {
        $this->assertFalse($this->collection->hasCollection(md5(__FUNCTION__)));
        $this->collection->append(new Stack(
            __FUNCTION__,
            md5(__FUNCTION__),
            __FILE__,
            1,
            __CLASS__,
            __FUNCTION__,
            'A',
            Mutex::ACTION_GET,
            '',
            new DateTime()
        ));
        $this->assertTrue($this->collection->hasCollection(md5(__FUNCTION__)));
    }

    /**
     * Получение коллекции по хешу запроса
     */
    public function testGetCollectionByRequestHash()
    {
        $this->collection->append(new Stack(
            __FUNCTION__,
            md5(__FUNCTION__),
            __FILE__,
            1,
            __CLASS__,
            __FUNCTION__,
            'A',
            Mutex::ACTION_GET,
            '',
            new DateTime()
        ));
        $this->assertNotNull($this->collection->getCollectionByRequestHash(md5(__FUNCTION__)));
    }

    /**
     * Попытка получить несуществующую коллекцию
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testGetCollectionByRequestHashNotFound()
    {
        $this->collection->getCollectionByRequestHash(md5(__FUNCTION__));
    }

    /**
     * Тестирование уникальных коллекций
     */
    public function testGetUniqueCollections()
    {
        $request1 = new Stack(
            __FUNCTION__,
            md5(__FUNCTION__ . 1),
            __FILE__,
            1,
            __CLASS__,
            __FUNCTION__,
            'A',
            Mutex::ACTION_GET,
            '',
            new DateTime()
        );

        $request2 = new Stack(
            __FUNCTION__,
            md5(__FUNCTION__ . 2),
            __FILE__,
            1,
            __CLASS__,
            __FUNCTION__,
            'A',
            Mutex::ACTION_GET,
            '',
            new DateTime()
        );

        $this->assertTrue($request1->getModelHash() === $request2->getModelHash());

        $this->collection
            ->append($request1)
            ->append($request2);

        $this->assertEquals(2, count($this->collection));
        $this->assertEquals(1, count($this->collection->getUniqueCollections()));
    }
}