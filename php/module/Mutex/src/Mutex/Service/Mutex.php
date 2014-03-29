<?php

/**
 * PHP-Erlang Mutex
 * Сервис блокировок для обработки критических секций
 *
 * @category Mutex
 * @package  Mutex
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/mutex
 */

namespace Mutex\Service;

use Mutex\Exception\Exception;
use Mutex\LoggerInterface;

/**
 * Class Mutex
 *
 * @package Mutex
 * @author  Sergey Yastrebov <serg.yastrebov@gmail.com>
 */
class Mutex
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 7007;

    /**
     * @var string
     */
    private $_hostname;

    /**
     * @var int
     */
    private $_port;

    /**
     * @var resource
     */
    private $_socket;

    /**
     * @var string
     */
    private $_name;

    /**
     * @var Profiler
     */
    private $_profiler;

    /**
     * @var LoggerInterface
     */
    private $_logger;

    /**
     * Constructor
     *
     * @param string $hostname Хост сервиса блокировок
     * @param int    $port     Порт сервиса блокировок (по умолчанию 7007)
     *
     * @throws Exception
     */
    public function __construct($hostname=self::DEFAULT_HOST, $port=self::DEFAULT_PORT)
    {
        $this->_hostname = $hostname;
        $this->_port     = $port;
    }

    /**
     * Отладчик
     *
     * @param Profiler $profiler
     * @return $this
     */
    public function setProfiler(Profiler $profiler)
    {
        $this->_profiler = $profiler;
        return $this;
    }

    /**
     * Логирование исключительных ситуаций вызова блокировок
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->_logger = $logger;
        return $this;
    }

    /**
     * Доступно ли подключение к сервису
     *
     * @return bool
     */
    public function isAlive()
    {
        return $this->_socket && !feof($this->_socket);
    }

    /**
     * Подключиться к сервису блокировок
     *
     * @throws \Mutex\Exception\Exception
     * @return $this
     */
    public function establishConnection()
    {
        $this->_socket = @fsockopen($this->_hostname, $this->_port, $errno, $errstr);
        if (!$this->_socket) {
            if ($errno === 0) {
                throw new Exception(sprintf('%s', $errstr));
            }
            if ($this->_logger) {
                $this->_logger->insert(sprintf('%s (%s)', $errstr, $errno));
            }
        }

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
    public function get($name, $timeout=false)
    {
        $this->_name = null;

        if ((!is_int($name) && !is_string($name)) || strlen($name) == 0) {
            throw new Exception('Недопустимое имя блокировки.');
        }
        if ((!is_int($timeout) && $timeout !== false) || (is_int($timeout) && $timeout < 0)) {
            throw new Exception('Недопустимое имя блокировки.');
        }

        $this->send(array(
            'cmd'     => 'get',
            'name'    => $name,
            'timeout' => $timeout,
        ));

        $response = $this->receive();
        if ($this->isAlive() && $response != $name) {
            throw new Exception(sprintf('Не удалось получить указатель на блокировку, причина: %s', $response));
        }
        if ($this->_profiler) {
            $this->_profiler->log($name, $response);
        }

        $this->_name = $response;
        return $this->_name;
    }

    /**
     * Установить блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return bool
     */
    public function acquire($name=null)
    {
        $name = $name ? $name : $this->_name;
        if ($name) {
            while (true) {
                $this->send(array('cmd' => 'acquire', 'name' => $name));
                $response = $this->receive();

                if ($this->_profiler) {
                    $this->_profiler->log($name, $response);
                }

                switch ($response) {
                    case 'acquired':
                    case 'already_acquired':
                        return true;
                    case 'busy':
                        usleep(10000);
                        continue;
                        break;
                    case 'not_found':
                    default:
                        return true;
                }
            }
        }

        return false;
    }

    /**
     * Снять блокировку
     *
     * @param string $name Имя указателя блокировки
     * @return bool
     */
    public function release($name=null)
    {
        $name = $name ? $name : $this->_name;
        if ($name) {
            $this->send(array('cmd' => 'release', 'name' => $name));
            $response = $this->receive();

            if ($this->_profiler) {
                $this->_profiler->log($name, $response);
            }

            switch ($response) {
                case 'released':
                    return true;
                case 'not_found':
                default:
                    return true;
            }
        }

        return false;
    }

    /**
     * Закрыть соединение с сервисом блокировок
     */
    public function __destruct()
    {
        if ($this->_socket) {
            @fclose($this->_socket);
        }
    }

    /**
     * Отправить запрос
     *
     * @param array $data отправляемый запрос на сервис
     * @return bool
     */
    protected function send(array $data)
    {
        if ($this->isAlive()) {
            @fwrite($this->_socket, json_encode($data));
            return true;
        }

        return false;
    }

    /**
     * Получить ответ
     *
     * @return string
     */
    protected function receive()
    {
        $input = '';
        while (false !== ($char = @fgetc($this->_socket))) {
            if ($char === "\000") {
                return $input;
            } else {
                $input .= $char;
            }
        }

        return null;
    }
}