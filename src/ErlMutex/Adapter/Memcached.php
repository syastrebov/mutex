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
     * Constructor
     *
     * @param \Memcached $adapter
     */
    public function __construct(\Memcached $adapter)
    {
        $this->adapter = $adapter;
    }
}