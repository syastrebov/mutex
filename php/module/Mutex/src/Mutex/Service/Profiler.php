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

use Mutex\Exception\ProfilerException as Exception;
use Mutex\Model\ProfileStack as ProfileStackModel;
use Mutex\ProfilerStorageInterface;
use DateTime;

/**
 * Профайлер отладчик для mutex'a
 * Строит карту вызова блокировок
 *
 * Class Profiler
 * @package Mutex
 */
class Profiler
{
    /**
     * Время инициализации профайлера
     *
     * @var DateTime
     */
    private $_initDateTime;

    /**
     * Запрашиваемый адрес (точка входа)
     *
     * @var string
     */
    private $_requestUri;

    /**
     * Стек вызова блокировок
     *
     * @var array
     */
    private $_stack = array();

    /**
     * Хранилище истории блокировок
     *
     * @var ProfilerStorageInterface
     */
    private $_storage;

    /**
     * @var string
     */
    private $_mapOutputLocation;

    /**
     * Constructor
     *
     * @param string $requestUri Точка входа
     * @throws Exception
     */
    public function __construct($requestUri)
    {
        if (!is_string($requestUri)) {
            throw new Exception('Недопустимый request uri');
        }

        $this->_requestUri   = $requestUri;
        $this->_initDateTime = new DateTime();
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
     * Уникальный ключ запроса
     * Применяется для разделения истории запросов
     *
     * @return string
     */
    public function getRequestHash()
    {
        return md5($this->getRequestUri() . $this->_initDateTime->format('Y.m.d H:i:s'));
    }

    /**
     * Хранилище стека вызова
     * Для построения карты блокировок
     *
     * @param ProfilerStorageInterface $storage
     * @return $this
     */
    public function setStorage(ProfilerStorageInterface $storage)
    {
        $this->_storage = $storage;
        return $this;
    }

    /**
     * Путь к файлам сгенерированной карты вызовов
     *
     * @param string $mapOutputLocation
     * @return $this
     */
    public function setMapOutputLocation($mapOutputLocation)
    {
        $this->_mapOutputLocation = $mapOutputLocation;
        return $this;
    }

    /**
     * Залогировать вызов метода
     *
     * @param string $key
     * @param mixed  $response
     * @param array  $stackTrace
     */
    public function log($key, $response, array $stackTrace)
    {
        if (is_array($stackTrace) && count($stackTrace) > 1) {
            $entry = $stackTrace[1];
            $model = new ProfileStackModel(
                $this->getRequestUri(),
                $this->getRequestHash(),
                isset($entry['file'])     ? $entry['file']     : null,
                isset($entry['class'])    ? $entry['class']    : null,
                isset($entry['function']) ? $entry['function'] : null,
                isset($entry['line'])     ? $entry['line']     : null,
                $key,
                $response,
                new DateTime(),
                $stackTrace
            );

            $this->_stack[] = $model;
            if ($this->_storage) {
                $this->_storage->insert($model);
            }
        }
    }

    /**
     * Отобразить очередь вызова блокировок
     */
    public function dump()
    {
        foreach ($this->_stack as $trace) {
            /** @var ProfileStackModel $trace */
            self::debugMessage(
                sprintf(
                    "%s::%s (%s [%d]) key = %s, response = %s",
                    $trace->getClass(),
                    $trace->getMethod(),
                    $trace->getFile(),
                    $trace->getLine(),
                    $trace->getKey(),
                    $trace->getResponse()
                ),
                $trace->getDateTime()
            );
        }
    }

    /**
     * Построить карту вызова
     */
    public function map()
    {
        if (!$this->_storage) {
            throw new Exception('Не задано хранилище');
        }

        $map  = array();
        $list = $this->_storage->getList();

        foreach ($list as $trace) {
            /** @var ProfileStackModel $trace */
            if (!isset($requestUri[$trace->getRequestUri()][$trace->getRequestHash()])) {
                $map[$trace->getRequestUri()][$trace->getRequestHash()] = array();
            }

            $map[$trace->getRequestUri()][$trace->getRequestHash()][] = $trace;
        }

        return $map;
    }

    /**
     * Сгенерировать карту вызовов
     */
    public function generateMapHtmlOutput()
    {
        $map = $this->map();
    }

    /**
     * Отладочное сообщение
     *
     * @param string   $string
     * @param DateTime $time
     */
    public static function debugMessage($string, DateTime $time=null)
    {
        $time = $time ?: new DateTime;
        echo sprintf("%s on %s\r\n", $string, $time->format('H:i:s'));
        flush();
    }
} 