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

/**
 * Class Mutex
 *
 * @package Mutex
 * @author  Sergey Yastrebov <serg.yastrebov@gmail.com>
 */
class Mutex
{
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
     * Constructor
     *
     * @param string $hostname Хост сервиса блокировок
     * @param string $port     Порт сервиса блокировок (по умолчанию 7007)
     *
     * @throws Exception
     */
    public function __construct($hostname, $port)
    {
        $this->_socket = @fsockopen($hostname, $port, $errno, $errstr);
        if (!$this->_socket) {
            throw new Exception(sprintf('%s (%s)', $errstr, $errno));
        }
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
     * Получить указатель на блокировку
     *
     * @param string $name    Имя указателя блокировки
     * @param int    $timeout Время жизни блокировки (по истечении времени блокировка снимается)
     *
     * @return string
     * @throws Exception
     */
    public function get($name, $timeout)
    {
        $this->_name = null;

        if (!is_string($name) || !(strlen($name) > 0)) {
            throw new Exception('Невалидное имя блокировки.');
        }

        $this->send(array(
            'cmd'     => 'get',
            'name'    => $name,
            'timeout' => $timeout,
        ));

        $response = $this->receive();
        if ($response != $name) {
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
     *
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
                        return false;
                }
            }
        }

        return false;
    }

    /**
     * Снять блокировку
     *
     * @param string $name Имя указателя блокировки
     *
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
                    return false;
            }
        }

        return false;
    }

    /**
     * Закрыть соединение с сервисом блокировок
     */
    public function __destruct()
    {
        fclose($this->_socket);
    }

    /**
     * Отправить запрос
     *
     * @param array $data отправляемый запрос на сервис
     *
     * @return bool
     */
    protected function send(array $data)
    {
        if ($this->_socket) {
            fwrite($this->_socket, json_encode($data));
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
        while (false !== ($char = fgetc($this->_socket))) {
            if ($char === "\000") {
                return $input;
            } else {
                $input .= $char;
            }
        }

        return null;
    }
}