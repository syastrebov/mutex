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

namespace ErlMutex\Test\Validator;

use ErlMutex\Validator\ProfilerActionsOrder;
use ErlMutex\Validator\ProfilerCollection;

/**
 * Тестирование коллекции валидаторов
 *
 * Class ProfilerCollectionTest
 * @package ErlMutex\Test\Validator
 */
class ProfilerCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Колекция валидаторов
     *
     * @var ProfilerCollection
     */
    private $collection;

    /**
     * Задаем новую коллекцию для каждого теста
     */
    public function setUp()
    {
        $this->collection = new ProfilerCollection();
    }

    /**
     * Удаляем коллекцию после каждого теста
     */
    public function tearDown()
    {
        $this->collection = null;
    }

    /**
     * Повторное добавление валидатора
     *
     * @expectedException \ErlMutex\Exception\ProfilerException
     */
    public function testAlreadyAppended()
    {
        $this->collection->append(new ProfilerActionsOrder());
        $this->collection->append(new ProfilerActionsOrder());
    }
}