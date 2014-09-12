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

use ErlMutex\Exception\Exception;

/**
 * Адаптер для работы через memcached
 *
 * Class Memcached
 * @package ErlMutex\Adapter
 */
final class Memcached implements AdapterInterface
{
    /**
     * @var \Memcached
     */
    private $adapter;

    /**
     * Время жизни блокировок
     *
     * @var array
     */
    private $timeout = [];

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
     * Подключиться к сервису блокировок
     *
     * @throws \ErlMutex\Exception\Exception
     * @return boolean
     */
    public function establishConnection()
    {
        return true;
    }

    /**
     * Закрыть соединение с сервисом
     */
    public function closeConnection() {}

    /**
     * Получить указатель на блокировку
     *
     * @param string   $name    Имя указателя блокировки
     * @param int|bool $timeout Время жизни блокировки, микросекунды (по истечении времени блокировка снимается)
     *
     * @return string
     * @throws Exception
     */
    public function get($name, $timeout)
    {
        $this->timeout[$name] = $timeout;
    }

    /**
     * Установить блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return bool
     */
    public function acquire($name)
    {
        while (true) {
            if ($this->adapter->get($name)) {
                usleep(10000);
            } else {
                break;
            }
        }

        return $this->adapter->set($name, true, isset($this->timeout[$name]) ? $this->timeout[$name] : 30);
    }

    /**
     * Снять блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return bool
     */
    public function release($name)
    {
        return $this->adapter->delete($name);
    }

    /**
     * Доступно ли подключение к сервису
     *
     * @return bool
     */
    public function isAlive()
    {
        return true;
    }
}