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
 * Базовый адаптер для кеша
 *
 * Class AbstractCache
 * @package ErlMutex\Adapter
 */
abstract class AbstractCache implements AdapterInterface
{
    const TIMEOUT_DEFAULT = 5;
    const TIMEOUT_MAX     = 10;

    /**
     * Время жизни блокировок
     *
     * @var array
     */
    private $timeout = [];

    /**
     * Подключиться к сервису блокировок
     */
    public function establishConnection() {}

    /**
     * Закрыть соединение с сервисом
     */
    public function closeConnection() {}

    /**
     * Получить указатель на блокировку
     *
     * @param string   $name    Имя указателя блокировки
     * @param int|bool $timeout Время жизни блокировки, милисекунды (по истечении времени блокировка снимается)
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
            if ($this->getCache($name)) {
                usleep(10000);
            } else {
                break;
            }
        }

        $timeout = self::TIMEOUT_DEFAULT;
        if (isset($this->timeout[$name])) {
            $timeout = (int)($this->timeout[$name] / 1000);
            if (!$timeout) {
                $timeout = 1;
            }
        }
        if ($timeout > self::TIMEOUT_MAX) {
            $timeout = self::TIMEOUT_MAX;
        }

        return $this->setCache($name, true, $timeout);
    }

    /**
     * Снять блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return bool
     */
    public function release($name)
    {
        return $this->deleteCache($name);
    }

    /**
     * Доступно ли подключение к сервису
     *
     * @return bool
     */
    public function isAlive()
    {
        return $this->setCache(md5(__CLASS__ . __METHOD__), true);
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
    abstract protected function setCache($key, $value, $timeout = null);

    /**
     * Получить значение из кеша
     *
     * @param string $key
     * @return mixed
     */
    abstract protected function getCache($key);

    /**
     * Удалить значение из кеша
     *
     * @param $key
     * @return bool
     */
    abstract protected function deleteCache($key);
}