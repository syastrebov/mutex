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
 * Class ProfileStack
 * @package erl\Model
 */
class ProfileStack
{
    /**
     * @var string
     */
    private $_requestUri;

    /**
     * @var string
     */
    private $_requestHash;

    /**
     * @var string
     */
    private $_filename;

    /**
     * @var string
     */
    private $_class;

    /**
     * @var string
     */
    private $_method;

    /**
     * @var int
     */
    private $_line;

    /**
     * @var string
     */
    private $_key;

    /**
     * @var mixed
     */
    private $_response;

    /**
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
     * @param string   $class
     * @param string   $method
     * @param int      $line
     * @param string   $key
     * @param mixed    $response
     * @param DateTime $dateTime
     * @param string   $stackTrace
     */
    public function __construct(
        $requestUri,
        $requestHash,
        $filename,
        $class,
        $method,
        $line,
        $key,
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
     * Сервис вернул ответ
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @return DateTime
     */
    public function getDateTime()
    {
        return clone $this->_dateTime;
    }

    /**
     * @return string
     */
    public function getDateTimeFormat()
    {
        return $this->_dateTime->format('Y.m.d H:i:s');
    }

    /**
     * @return null|string
     */
    public function getStackTrace()
    {
        return $this->_stackTrace;
    }
}