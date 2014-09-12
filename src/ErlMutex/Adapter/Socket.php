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
use ErlMutex\LoggerInterface;

/**
 * Адаптер для работы через socket
 *
 * Class Socket
 * @package ErlMutex\Adapter
 */
final class Socket implements AdapterInterface
{
    const DEFAULT_HOST   = '127.0.0.1';
    const DEFAULT_PORT   = 7007;

    const ACTION_GET     = 'get';
    const ACTION_ACQUIRE = 'acquire';
    const ACTION_RELEASE = 'release';

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var int
     */
    private $port;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string
     */
    private $name;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string $hostname Хост сервиса блокировок
     * @param int    $port     Порт сервиса блокировок (по умолчанию 7007)
     *
     * @throws Exception
     */
    public function __construct($hostname = self::DEFAULT_HOST, $port = self::DEFAULT_PORT)
    {
        $this->hostname = $hostname;
        $this->port     = $port;
    }

    /**
     * Подключиться к сервису блокировок
     *
     * @throws \ErlMutex\Exception\Exception
     * @return $this
     */
    public function establishConnection()
    {
        $this->socket = @fsockopen($this->hostname, $this->port, $errno, $errstr);
        if (!$this->socket) {
            if ($errno === 0) {
                throw new Exception(sprintf('%s', $errstr));
            }
            if ($this->logger) {
                $this->logger->insert(sprintf('%s (%s)', $errstr, $errno));
            }
        }

        return $this;
    }

    /**
     * Закрыть соединение с сервисом
     */
    public function closeConnection()
    {
        if ($this->socket) {
            @fclose($this->socket);
        }

        $this->socket = null;
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

        $this->send(array(
            'cmd'     => self::ACTION_GET,
            'name'    => $name,
            'timeout' => $timeout,
        ));

        $response   = $this->receive();
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
            $response = null;
            while (true) {
                $this->send(['cmd' => self::ACTION_ACQUIRE, 'name' => $name]);
                $response = $this->receive();
                if ($response == 'busy') {
                    usleep(10000);
                } else {
                    break;
                }
            }

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
            $this->send(['cmd' => self::ACTION_RELEASE, 'name' => $name]);
            $response = $this->receive();
            $this->log($name, $response, debug_backtrace());

            return true;

        } else {
            $this->log($name, 'Не задан указатель', debug_backtrace());
        }

        return false;
    }

    /**
     * Доступно ли подключение к сервису
     *
     * @return bool
     */
    public function isAlive()
    {
        return $this->socket && !feof($this->socket);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * Отправить запрос
     *
     * @param array $data отправляемый запрос на сервис
     * @return bool
     */
    private function send(array $data)
    {
        if ($this->isAlive()) {
            @fwrite($this->socket, json_encode($data));
            return true;
        }

        return false;
    }

    /**
     * Получить ответ
     *
     * @return string
     */
    private function receive()
    {
        $input = '';
        while (false !== ($char = @fgetc($this->socket))) {
            if ($char === "\000") {
                return $input;
            } else {
                $input .= $char;
            }
        }

        return null;
    }
}