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

/**
 * Коллекция моделей лога профайлера
 *
 * Class ProfilerStackCollection
 * @package ErlMutex\Model
 */
class ProfilerStackCollection extends AbstractCollection
{
    /**
     * Уникальный хеш запроса
     *
     * @var string
     */
    private $requestHash;

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
     * Запрашиваемый адрес (точка входа)
     *
     * @return string
     * @throws Exception
     */
    public function getRequestUri()
    {
        if (!empty($this->collection)) {
            /** @var ProfilerStackModel $trace */
            $trace = $this->collection[0];
            return $trace->getRequestUri();
        }

        throw new Exception('Коллекция пуста');
    }

    /**
     * Уникальный хеш модели
     *
     * @return string
     */
    public function getModelHash()
    {
        $hash = $this->requestHash;
        foreach ($this->collection as $trace) {
            /** @var ProfilerStackModel $trace */
            $hash .= $trace->getModelHash();
        }

        return md5($hash);
    }

    /**
     * Получить все ключи хранимые в коллекции
     *
     * @return array
     */
    public function getKeys()
    {
        $keys = array();
        foreach ($this->collection as $request) {
            /** @var ProfilerStackModel $request */
            if (!in_array($request->getKey(), $keys, true)) {
                $keys[] = $request->getKey();
            }
        }

        return $keys;
    }

    /**
     * Преобразовать коллекцию в массив
     *
     * @return array
     */
    public function asArray()
    {
        $result = array();
        foreach ($this->collection as $trace) {
            /** @var ProfilerStackModel $trace */
            $result[] = $trace->asArray();
        }

        return $result;
    }
} 