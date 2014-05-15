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

namespace ErlMutex\Model;

use ErlMutex\Exception\ProfilerException as Exception;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use Iterator;

/**
 * Коллекция моделей лога профайлера
 *
 * Class ProfilerStackCollection
 * @package ErlMutex\Model
 */
class ProfilerStackCollection implements Iterator
{
    /**
     * Уникальный хеш запроса
     *
     * @var string
     */
    private $requestHash;

    /**
     * Массив запросов
     *
     * @var array
     */
    private $collection = array();

    /**
     * Constructor
     *
     * @param string $requestHash
     */
    public function __construct($requestHash)
    {
        $this->requestHash = $requestHash;
    }

    /**
     * Добавить запрос в коллекцию
     *
     * @param ProfilerStack $trace
     * @return $this
     * @throws Exception
     */
    public function append(ProfilerStackModel $trace)
    {
        if ($trace->getRequestHash() !== $this->requestHash) {
            throw new Exception('Передан запрос с неправильным хешом');
        }

        $this->collection[] = $trace;
        return $this;
    }

    /**
     * Уникальный хеш запроса
     *
     * @return string
     */
    public function getRequestHash()
    {
        return $this->requestHash;
    }

    /**
     * Уникальный хеш модели
     *
     * @return string
     */
    public function getModelHash()
    {
        $hash = '';
        foreach ($this->collection as $trace) {
            /** @var ProfilerStackModel $trace */
            $hash .= $trace->getModelHash();
        }

        return md5($hash);
    }

    /**
     * Return the current element
     *
     * @return mixed Can return any type.
     */
    public function current()
    {
        return current($this->collection);
    }

    /**
     * Move forward to next element
     *
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        next($this->collection);
    }

    /**
     * Return the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return key($this->collection);
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {

    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        reset($this->collection);
    }
} 