<?php

namespace Mutex;

/**
 * Class Mutex
 * @package Mutex
 */
class Mutex
{
    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string
     */
    private $name;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $port
     *
     * @throws Exception
     */
    public function __construct($hostname, $port)
    {
        $this->socket = fsockopen($hostname, $port, $errno, $errstr);
        if (!$this->socket) {
            throw new Exception(sprintf('%s (%s)', $errstr, $errno));
        }
    }

    /**
     * Получить указатель на блокировку
     *
     * @param string $name
     * @param int    $timeout
     *
     * @return string
     * @throws Exception
     */
    public function get($name, $timeout)
    {
        $this->name = null;

        if (!is_string($name) || !(strlen($name) > 0)) {
            throw new Exception('Невалидное имя блокировки.');
        }

        $this->send(array(
            'cmd'     => 'get',
            'name'    => $name,
            'timeout' => $timeout
        ));

        $response = $this->receive();
        if ($response != $name) {
            throw new Exception(sprintf('Не удалось получить указатель на блокировку, причина: %s', $response));
        }

        $this->name = $response;
        return $this->name;
    }

    /**
     * Установить блокировку
     *
     * @param string $name
     * @return bool
     */
    public function acquire($name=null)
    {
        $name = $name ? $name : $this->name;
        if ($name) {
            while (true) {
                $this->send(array('cmd' => 'acquire', 'name' => $name));
                $response = $this->receive();

                switch ($response) {
                    case 'acquired':
                    case 'already_acquired':
                        return true;
                    case 'busy':
                        usleep(10000);
                        continue;
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
     * @param string $name
     * @return bool
     */
    public function release($name=null)
    {
        $name = $name ? $name : $this->name;
        if ($name) {
            $this->send(array('cmd' => 'release', 'name' => $name));
            $response = $this->receive();

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
        fclose($this->socket);
    }

    /**
     * Отправить запрос
     *
     * @param $data
     */
    protected function send($data)
    {
        if ($this->socket) {
            fwrite($this->socket, json_encode($data));
        }
    }

    /**
     * Получить ответ
     *
     * @return string
     */
    protected function receive()
    {
        $input = '';
        while (false !== ($char = fgetc($this->socket))) {
            if ($char === "\000") {
                return $input;
            } else {
                $input .= $char;
            }
        }

        return null;
    }
}