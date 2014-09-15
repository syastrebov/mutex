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

namespace ErlMutex\Adapter;

/**
 * Адаптер для работы через memcached
 *
 * Class Memcached
 * @package ErlMutex\Adapter
 */
final class Memcached extends AbstractCache
{
    /**
     * @var \Memcached
     */
    private $adapter;

    /**
     * Constructor
     *
     * @param \Memcached $adapter
     */
    public function __construct(\Memcached $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Установить значение в кеш
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $timeout
     *
     * @return bool
     */
    protected function setCache($key, $value, $timeout = null)
    {
        return $this->adapter->set($key, true, $timeout);
    }

    /**
     * Получить значение из кеша
     *
     * @param string $key
     * @return mixed
     */
    protected function getCache($key)
    {
        return $this->adapter->get($key);
    }

    /**
     * Удалить значение из кеша
     *
     * @param $key
     * @return bool
     */
    protected function deleteCache($key)
    {
        return $this->adapter->delete($key);
    }
}