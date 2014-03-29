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
 * Отладчик для mutex'a
 * Строит карту вызова блокировок
 *
 * Class Profiler
 * @package Mutex
 */
class Profiler
{
    /**
     * @var string
     */
    private $_requestUri;

    /**
     * @var array
     */
    private $_stack = array();

    /**
     * @var ProfilerStorageInterface
     */
    private $_storage;

    /**
     * Constructor
     *
     * @param string $requestUri Точка входа
     */
    public function __construct($requestUri)
    {
        $this->_requestUri = $requestUri;
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
     * Зафиксировать вызов метода
     *
     * @param string $key
     * @param mixed  $response
     */
    public function log($key, $response)
    {
        $stackTrace = debug_backtrace();
        if (is_array($stackTrace) && count($stackTrace) > 1) {
            $entry = $stackTrace[1];

            $this->_stack[] = new ProfileStackModel(
                isset($entry['file'])     ? $entry['file']     : null,
                isset($entry['class'])    ? $entry['class']    : null,
                isset($entry['function']) ? $entry['function'] : null,
                isset($entry['line'])     ? $entry['line']     : null,
                $key,
                $response,
                new DateTime(),
                $stackTrace
            );
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
     * Сохранить очередь вызова
     */
    public function save()
    {

    }

    /**
     * Построить карту вызова
     */
    public function map()
    {
        if (!$this->_storage) {
            throw new Exception('Не задано хранилище');
        }

        $list = $this->_storage->getList();
        foreach ($list as $trace) {
            /** @var ProfileStackModel $trace */
        }
    }

    /**
     * Отладочное сообщение
     *
     * @param string   $str
     * @param DateTime $time
     */
    public static function debugMessage($str, DateTime $time=null)
    {
        $time = $time ?: new DateTime;
        echo sprintf("%s on %s\r\n", $str, $time->format('H:i:s'));
        flush();
    }
} 