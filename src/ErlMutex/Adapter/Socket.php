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
use ErlMutex\Service\Mutex;

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
     */
    public function establishConnection()
    {
        $this->socket = @fsockopen($this->hostname, $this->port, $errorCode, $errorMessage);
        if (!$this->socket) {
            throw new Exception(sprintf('%s (%s)', $errorMessage, $errorCode));
        }
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
     * @param int|bool $timeout Время жизни блокировки, милисекунды (по истечении времени блокировка снимается)
     *
     * @return string
     * @throws Exception
     */
    public function get($name, $timeout)
    {
        $this->send(array(
            'cmd'     => Mutex::ACTION_GET,
            'name'    => $name,
            'timeout' => $timeout,
        ));

        return $this->receive();
    }

    /**
     * Установить блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return string
     */
    public function acquire($name)
    {
        $response = null;
        while (true) {
            $this->send(['cmd' => Mutex::ACTION_ACQUIRE, 'name' => $name]);
            $response = $this->receive();
            if ($response == 'busy') {
                usleep(10000);
            } else {
                break;
            }
        }

        return $response;
    }

    /**
     * Снять блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return string
     */
    public function release($name)
    {
        $this->send(['cmd' => Mutex::ACTION_RELEASE, 'name' => $name]);
        return $this->receive();
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