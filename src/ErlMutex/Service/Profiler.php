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

namespace ErlMutex\Service;

use ErlMutex\Exception\ProfilerException as Exception;
use ErlMutex\Model\ProfileStack as ProfileStackModel;
use ErlMutex\ProfilerStorageInterface;
use DateTime;

/**
 * Профайлер отладчик для erl'a
 * Строит карту вызова блокировок
 *
 * Class Profiler
 * @package erl
 */
class Profiler
{
    const TEMPLATES_DIR = '/../../../view';
    const PUBLIC_DIR    = '/../../../public';

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
     * Хранилище стека вызова
     * Для построения карты блокировок
     *
     * @return ProfilerStorageInterface
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * Путь к файлам сгенерированной карты вызовов
     *
     * @param string $mapOutputLocation
     * @return $this
     * @throws Exception
     */
    public function setMapOutputLocation($mapOutputLocation)
    {
        $this->_mapOutputLocation = $mapOutputLocation;
        if (!is_dir($mapOutputLocation)) {
            throw new Exception('Директория для генерации карты не найдена');
        }

        return $this;
    }

    /**
     * Логировать вызов метода
     *
     * @param string $key
     * @param mixed  $response
     * @param array  $stackTrace
     */
    public function log($key, $response, array $stackTrace)
    {
        $model = null;

        if (is_array($stackTrace) && !empty($stackTrace)) {
            if (count($stackTrace) > 1) {
                $entry     = $stackTrace[1];
                $className = isset($entry['class'])    ? $entry['class']    : null;
                $method    = isset($entry['function']) ? $entry['function'] : null;
            }

            $entry = $stackTrace[0];
            $model = new ProfileStackModel(
                $this->getRequestUri(),
                $this->getRequestHash(),
                isset($entry['file']) ? $entry['file'] : null,
                isset($entry['line']) ? $entry['line'] : null,
                isset($className)     ? $className     : null,
                isset($method)        ? $method        : null,
                $key,
                $response,
                new DateTime(),
                $stackTrace
            );
        }

        if ($model instanceof ProfileStackModel) {
            $this->_stack[] = $model;
            if ($this->_storage) {
                $this->_storage->insert($model);
            }
        }
    }

    /**
     * Отобразить очередь вызова блокировок
     * Выводит стек вызова за текущую сессию
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
     *
     * trace может возвращаться в виде ProfileStackModel или массива
     * Возвращает в формате:
     * - requestUri
     *      - requestHash 1
     *          * trace 1
     *          * trace 2
     *          ...
     *      - requestHash 2
     *          * trace 1
     *          * trace 2
     *          ...
     *
     * @param bool $traceAsArray
     * @return array
     * @throws Exception
     */
    public function map($traceAsArray=false)
    {
        if (!$this->_storage) {
            throw new Exception('Не задано хранилище');
        }

        $map  = array();
        $list = $this->_storage->getList();

        foreach ($list as $trace) {
            /** @var ProfileStackModel $trace */
            if (!isset($map[$trace->getRequestUri()][$trace->getRequestHash()])) {
                $map[$trace->getRequestUri()][$trace->getRequestHash()] = array();
            }

            $map[$trace->getRequestUri()][$trace->getRequestHash()][] = $traceAsArray ? $trace->asArray() : $trace;
        }

        return $map;
    }

    /**
     * Сгенерировать карту вызовов
     */
    public function generateHtmlMapOutput()
    {
        if (!$this->_mapOutputLocation) {
            throw new Exception('Не задана директория для генерации карты профайлера');
        }

        $map    = $this->map(true);
        $loader = new \Twig_Loader_Filesystem(__DIR__ . self::TEMPLATES_DIR);
        $twig   = new \Twig_Environment($loader);

        $output = $twig->render('profiler_map.twig', array(
            'map'     => $map,
            'cssFile' => __DIR__ . self::PUBLIC_DIR  . '/css/main.css',
        ));

        file_put_contents($this->_mapOutputLocation . '/profiler_map.html', $output);
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