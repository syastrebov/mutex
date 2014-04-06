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
use ErlMutex\Model\ProfilerCrossOrder;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
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
            $model = new ProfilerStackModel(
                $this->getRequestUri(),
                $this->getRequestHash(),
                isset($entry['file']) ? $entry['file'] : null,
                isset($entry['line']) ? $entry['line'] : null,
                isset($className)     ? $className     : null,
                isset($method)        ? $method        : null,
                $key,
                isset($entry['function']) ? $entry['function'] : null,
                $response,
                new DateTime(),
                $stackTrace
            );
        }

        if ($model instanceof ProfilerStackModel) {
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
            /** @var ProfilerStackModel $trace */
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
     * trace может возвращаться в виде ProfilerStackModel или массива
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
            /** @var ProfilerStackModel $trace */
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

        $exception = null;
        try {
            $map = $this->map();
            foreach ($map as $requests) {
                foreach ($requests as $traceList) {
                    $this->checkTraceHashList($traceList);
                }
            }
        } catch (Exception $e) {
            $exception = $e->getMessage();
        }

        $map    = $this->map(true);
        $loader = new \Twig_Loader_Filesystem(__DIR__ . self::TEMPLATES_DIR);
        $twig   = new \Twig_Environment($loader);

        $output = $twig->render('profiler_map.twig', array(
            'map'     => $map,
            'cssFile' => __DIR__ . self::PUBLIC_DIR  . '/css/main.css',
            'error'   => $exception,
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

    /**
     * Провекра последовательности вызова блокировок для хеша
     *
     *  - Проверка последовательности вызова блокировок по ключу для хеша
     *  - Проверка перехлестных вызовов блокировок
     *
     * При возникновении ошибок возвращает исключение
     *
     * @param array $traceList
     */
    private function checkTraceHashList(array $traceList)
    {
        $this->checkHashKeysActionsOrder($traceList);
        $this->checkCrossOrder($traceList);
    }

    /**
     * Проверка последовательности вызова блокировок по ключу для хеша
     * Если хотя бы один ключ вызван с неправильной последовательностью, функция возвращает исключение
     *
     * @param array $traceList
     */
    private function checkHashKeysActionsOrder(array $traceList)
    {
        $map = array();
        foreach ($traceList as $pos => $trace) {
            /** @var ProfilerStackModel $trace */
            $map[$trace->getKey()][$pos] = $trace->getMethod();
        }
        foreach ($map as $key => $actions) {
            $this->checkKeyActionsOrder($key, $actions);
        }
    }

    /**
     * Проверка последовательности вызова блокировок по ключу
     *
     * Правильная последовательность:
     *  - get(Key)
     *  - acquire(Key)
     *  - release(Key)
     * Если последовательность не совпадает, то функция возвращает исключение
     *
     * @param string $key
     * @param array $actions
     *
     * @throws \ErlMutex\Exception\ProfilerException
     */
    private function checkKeyActionsOrder($key, array $actions)
    {
        $wasGet     = false;
        $wasAcquire = false;

        foreach ($actions as $action) {
            switch ($action) {
                case Mutex::ACTION_GET:
                    if ($wasGet) {
                        throw new Exception(sprintf('Action get was already for key `%s`', $key));
                    }
                    $wasGet = true;

                    break;
                case Mutex::ACTION_ACQUIRE:
                    if (!$wasGet) {
                        throw new Exception(sprintf('Action get not found for key `%s`', $key));
                    }
                    if ($wasAcquire) {
                        throw new Exception(sprintf('Action acquire was already for key `%s`', $key));
                    }
                    $wasGet     = false;
                    $wasAcquire = true;

                    break;
                case Mutex::ACTION_RELEASE:
                    if (!$wasAcquire) {
                        throw new Exception(sprintf('Action acquire not found for key `%s`', $key));
                    }
                    $wasAcquire = false;

                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Проверка перехлестных вызовов блокировок
     *
     * Например:
     *  - get A
     *  - get B
     *  - acquire A
     *  - acquire B
     *  - release A
     *  - release B
     *
     * Схема вызова:
     * <A>
     *  <B>
     *  </A>
     * </B>
     *
     * Должно быть:
     * <A>
     *  <B>
     *  </B>
     * </A>
     *
     * @param $mapHashList
     * @throws \ErlMutex\Exception\ProfilerException
     */
    private function checkCrossOrder(array $mapHashList)
    {
        $acquired = array();
        foreach ($mapHashList as $trace) {
            /** @var ProfilerStackModel $trace */
            $acquired[$trace->getKey()] = new ProfilerCrossOrder($trace->getKey());
        }

        foreach ($mapHashList as $trace) {
            /** @var ProfilerCrossOrder $keyCrossOrderModel */
            $keyCrossOrderModel = $acquired[$trace->getKey()];

            switch ($trace->getAction()) {
                case Mutex::ACTION_ACQUIRE:
                    $keyCrossOrderModel->acquire();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerCrossOrder $otherKeyCrossOrderModel */
                        if ($otherKeyCrossOrderModel->isAcquired()
                            && $otherKeyCrossOrderModel->getKey() !== $trace->getKey()
                        ) {
                            $otherKeyCrossOrderModel->addContainsKey($trace->getKey());
                        }
                    }

                    break;
                case Mutex::ACTION_RELEASE:
                    $keyCrossOrderModel->release();

                    if ($keyCrossOrderModel->hasContainsKeys()) {
                        throw new Exception(sprintf("Can't release `%s` while another keys acquired", $trace->getKey()));
                    }
                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerCrossOrder $otherKeyCrossOrderModel */
                        $otherKeyCrossOrderModel->removeContainsKey($trace->getKey());
                    }

                    break;
                default:
                    break;
            }
        }
    }
} 