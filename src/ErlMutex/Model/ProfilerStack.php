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
    private $requestUri;

    /**
     * Уникальный хеш запроса
     *
     * @var string
     */
    private $requestHash;

    /**
     * Файл, в котором был вызван мьютекс
     *
     * @var string
     */
    private $filename;

    /**
     * Строка, на которой вызван мьютекс
     *
     * @var int
     */
    private $line;

    /**
     * Класс вызова
     *
     * @var string
     */
    private $class;

    /**
     * Метод класса вызова
     *
     * @var string
     */
    private $method;

    /**
     * Запрашиваемая команда
     *
     * @var string
     */
    private $action;

    /**
     * Имя указателя блокировки
     *
     * @var string
     */
    private $key;

    /**
     * Возвращенный сервисом ответ
     *
     * @var mixed
     */
    private $response;

    /**
     * Время вызова
     *
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var null|string
     */
    private $stackTrace;

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
        $this->requestUri  = $requestUri;
        $this->requestHash = $requestHash;
        $this->filename    = $filename;
        $this->class       = $class;
        $this->method      = $method;
        $this->line        = $line;
        $this->key         = $key;
        $this->action      = $action;
        $this->response    = $response;
        $this->dateTime    = $dateTime;
        $this->stackTrace  = $stackTrace;
    }

    /**
     * Запрашиваемый адрес (точка входа)
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * Уникальный хеш запроса
     *
     * @return string
     */
    public function getRequestHash()
    {
        return $this->requestHash;
    }

    /**
     * Файл
     *
     * @return string
     */
    public function getFile()
    {
        return $this->filename;
    }

    /**
     * Строка
     *
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Класс
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Метод
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Имя указателя блокировки
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Запрашиваемая команда
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Сервис вернул ответ
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Время вызова в виде объекта
     *
     * @return DateTime
     */
    public function getDateTime()
    {
        return clone $this->dateTime;
    }

    /**
     * Время вызова в формате Y.m.d H:i:s
     *
     * @return string
     */
    public function getDateTimeFormat()
    {
        return $this->dateTime->format('Y.m.d H:i:s');
    }

    /**
     * Преобразовать в массив
     *
     * @return array
     */
    public function asArray()
    {
        return array(
            'requestUri'  => $this->requestUri,
            'requestHash' => $this->requestHash,
            'filename'    => $this->filename,
            'class'       => $this->class,
            'method'      => $this->method,
            'line'        => $this->line,
            'key'         => $this->key,
            'action'      => $this->action,
            'response'    => $this->response,
            'dateTime'    => $this->getDateTimeFormat(),
        );
    }
}