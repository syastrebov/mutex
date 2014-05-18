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

use ErlMutex\Model\ProfilerStack;
use ErlMutex\Model\ProfilerWrongOrderCollection;
use ErlMutex\Service\Mutex;

/**
 * Class ProfilerWrongOrderCollectionTest
 * @package ErlMutex\Test\Model
 */
class ProfilerWrongOrderCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProfilerWrongOrderCollection
     */
    private $collection;

    public function setUp()
    {
        $this->collection = new ProfilerWrongOrderCollection();
    }

    public function tearDown()
    {
        $this->collection = null;
    }

    /**
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