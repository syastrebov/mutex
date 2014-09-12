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

namespace ErlMutex\Service;

use ErlMutex\Adapter\AdapterInterface;
use ErlMutex\Exception\Exception;
use ErlMutex\LoggerInterface;

/**
 * Class erl
 *
 * @package erl
 * @author  Sergey Yastrebov <serg.yastrebov@gmail.com>
 */
class Mutex
{
    const ACTION_GET     = 'get';
    const ACTION_ACQUIRE = 'acquire';
    const ACTION_RELEASE = 'release';

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param AdapterInterface $adapter
     * @throws Exception
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Отладчик
     *
     * @param Profiler $profiler
     * @return $this
     */
    public function setProfiler(Profiler $profiler)
    {
        $this->profiler = $profiler;
        return $this;
    }

    /**
     * Отладчик
     *
     * @return Profiler
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * Логирование исключительных ситуаций вызова блокировок
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Доступно ли подключение к сервису
     *
     * @return bool
     */
    public function isAlive()
    {
        return $this->adapter->isAlive();
    }

    /**
     * Подключиться к сервису блокировок
     *
     * @throws \ErlMutex\Exception\Exception
     * @return $this
     */
    public function establishConnection()
    {
        try {
            $this->adapter->establishConnection();
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->insert($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Закрыть соединение с сервисом
     *
     * @return $this
     */
    public function closeConnection()
    {
        $this->adapter->closeConnection();
        return $this;
    }

    /**
     * Получить указатель на блокировку
     *
     * @param string   $name    Имя указателя блокировки
     * @param int|bool $timeout Время жизни блокировки, микросекунды (по истечении времени блокировка снимается)
     *
     * @return string
     * @throws Exception
     */
    public function get($name, $timeout = false)
    {
        $this->name = null;

        if ((!is_int($name) && !is_string($name)) || strlen($name) == 0 || $name === null) {
            throw new Exception('Недопустимое имя блокировки.');
        }
        if ((!is_int($timeout) && $timeout !== false) || (is_int($timeout) && $timeout < 0)) {
            throw new Exception('Недопустимое время блокировки.');
        }

        $response   = $this->adapter->get($name, $timeout);
        $this->name = $name;

        $this->log($name, $response, debug_backtrace());
        return $this->name;
    }

    /**
     * Установить блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return bool
     */
    public function acquire($name = null)
    {
        $name = $name ? : $this->name;
        if ($name) {
            $response = $this->adapter->acquire($name);
            $this->log($name, $response, debug_backtrace());

            return true;

        } else {
            $this->log($name, 'Не задан указатель', debug_backtrace());
        }

        return false;
    }

    /**
     * Снять блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return bool
     */
    public function release($name = null)
    {
        $name = $name ? : $this->name;
        if ($name) {
            $response = $this->adapter->release($name);
            $this->log($name, $response, debug_backtrace());

            return true;

        } else {
            $this->log($name, 'Не задан указатель', debug_backtrace());
        }

        return false;
    }

    /**
     * Залогировать вызов метода
     *
     * @param string $key
     * @param mixed  $response
     * @param array  $stackTrace
     */
    public function log($key, $response, $stackTrace)
    {
        if ($this->profiler) {
            $this->profiler->log($key, $response, $stackTrace);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->closeConnection();
    }
}