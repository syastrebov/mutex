<?php

/**
 * PHP-Erlang erl
 * Сервис блокировок для обработки критических секций
 *
 * @category erl
 * @package  erl
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/erl
 */

namespace ErlMutex\Model;

use DateTime;

/**
 * Модель лога профайлера
 *
 * Class ProfilerStack
 * @package erl\Model
 */
class ProfilerStack
{
    /**
     * Запрашиваемый адрес (точка входа)
     *
     * @var string
     */
    private $_requestUri;

    /**
     * Уникальный хеш запроса
     *
     * @var string
     */
    private $_requestHash;

    /**
     * Файл, в котором был вызван мьютекс
     *
     * @var string
     */
    private $_filename;

    /**
     * Строка, на которой вызван мьютекс
     *
     * @var int
     */
    private $_line;

    /**
     * Класс вызова
     *
     * @var string
     */
    private $_class;

    /**
     * Метод класса вызова
     *
     * @var string
     */
    private $_method;

    /**
     * Запрашиваемая команда
     *
     * @var string
     */
    private $_action;

    /**
     * Имя указателя блокировки
     *
     * @var string
     */
    private $_key;

    /**
     * Возвращенный сервисом ответ
     *
     * @var mixed
     */
    private $_response;

    /**
     * Время вызова
     *
     * @var DateTime
     */
    private $_dateTime;

    /**
     * @var null|string
     */
    private $_stackTrace;

    /**
     * Constructor
     *
     * @param string   $requestUri
     * @param string   $requestHash
     * @param string   $filename
     * @param int      $line
     * @param string   $class
     * @param string   $method
     * @param string   $key
     * @param string   $action
     * @param mixed    $response
     * @param DateTime $dateTime
     * @param string   $stackTrace
     */
    public function __construct(
        $requestUri,
        $requestHash,
        $filename,
        $line,
        $class,
        $method,
        $key,
        $action,
        $response,
        DateTime $dateTime,
        $stackTrace=null
    ) {
        $this->_requestUri  = $requestUri;
        $this->_requestHash = $requestHash;
        $this->_filename    = $filename;
        $this->_class       = $class;
        $this->_method      = $method;
        $this->_line        = $line;
        $this->_key         = $key;
        $this->_action      = $action;
        $this->_response    = $response;
        $this->_dateTime    = $dateTime;
        $this->_stackTrace  = $stackTrace;
    }

    /**
     * Запрашиваемый адрес (точка входа)
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->_requestUri;
    }

    /**
     * Уникальный хеш запроса
     *
     * @return string
     */
    public function getRequestHash()
    {
        return $this->_requestHash;
    }

    /**
     * Файл
     *
     * @return string
     */
    public function getFile()
    {
        return $this->_filename;
    }

    /**
     * Строка
     *
     * @return int
     */
    public function getLine()
    {
        return $this->_line;
    }

    /**
     * Класс
     *
     * @return string
     */
    public function getClass()
    {
        return $this->_class;
    }

    /**
     * Метод
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Имя указателя блокировки
     *
     * @return string
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Запрашиваемая команда
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Сервис вернул ответ
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Время вызова в виде объекта
     *
     * @return DateTime
     */
    public function getDateTime()
    {
        return clone $this->_dateTime;
    }

    /**
     * Время вызова в формате Y.m.d H:i:s
     *
     * @return string
     */
    public function getDateTimeFormat()
    {
        return $this->_dateTime->format('Y.m.d H:i:s');
    }

    /**
     * Преобразовать в массив
     *
     * @return array
     */
    public function asArray()
    {
        return array(
            'requestUri'  => $this->_requestUri,
            'requestHash' => $this->_requestHash,
            'filename'    => $this->_filename,
            'class'       => $this->_class,
            'method'      => $this->_method,
            'line'        => $this->_line,
            'key'         => $this->_key,
            'action'      => $this->_action,
            'response'    => $this->_response,
            'dateTime'    => $this->getDateTimeFormat(),
        );
    }
}