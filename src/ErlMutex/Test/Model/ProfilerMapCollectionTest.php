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

use ErlMutex\Model\ProfilerMapCollection;
use ErlMutex\Model\ProfilerStack;
use ErlMutex\Service\Mutex;
use DateTime;

/**
 * Class ProfilerMapCollectionTest
 * @package ErlMutex\Test\Model
 */
class ProfilerMapCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProfilerMapCollection
     */
    private $collection;

    /**
     *
     */
    public function setUp()
    {
        $this->collection = new ProfilerMapCollection();
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->collection = null;
    }

    public function testAppend()
    {

    }

    public function testHasCollection()
    {

    }

    public function testGetCollectionByRequestHash()
    {

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

    public function testGetUniqueCollections()
    {
        $request1 = new ProfilerStack(
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

        $request2 = new ProfilerStack(
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