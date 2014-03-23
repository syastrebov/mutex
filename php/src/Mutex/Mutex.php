<?php

/**
 * Class Mutex
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
     */
    public function __construct($hostname, $port)
    {
        $this->socket = fsockopen($hostname, $port, $errno, $errstr);
        if (!$this->socket) {
            echo "$errstr ($errno)<br />\n";
        }
    }

    /**
     * Получить указатель на блокировку
     *
     * @param string $name
     * @param int    $timeout
     *
     * @return string
     */
    public function get($name, $timeout)
    {
        $this->send(array('cmd' => 'get', 'name' => $name, 'timeout' => $timeout));
        $this->name = $this->receive();

        if (!is_string($this->name) || !strlen($this->name)) {
            $this->name = null;
        }

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