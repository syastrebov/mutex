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
use ErlMutex\Model\ProfilerMapCollection;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use ErlMutex\Model\ProfilerWrongOrder;
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
    private $initDateTime;

    /**
     * Запрашиваемый адрес (точка входа)
     *
     * @var string
     */
    private $requestUri;

    /**
     * Стек вызова блокировок
     *
     * @var array
     */
    private $stack = array();

    /**
     * Хранилище истории блокировок
     *
     * @var ProfilerStorageInterface
     */
    private $storage;

    /**
     * @var string
     */
    private $mapOutputLocation;

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

        $this->requestUri   = $requestUri;
        $this->initDateTime = new DateTime();
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
     * Уникальный ключ запроса
     * Применяется для разделения истории запросов
     *
     * @return string
     */
    public function getRequestHash()
    {
        return md5($this->getRequestUri() . $this->initDateTime->format('Y.m.d H:i:s'));
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
        $this->storage = $storage;
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
        return $this->storage;
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
        $this->mapOutputLocation = $mapOutputLocation;
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
            $this->stack[] = $model;
            if ($this->storage) {
                $this->storage->insert($model);
            }
        }
    }

    /**
     * Отобразить очередь вызова блокировок
     * Выводит стек вызова за текущую сессию
     */
    public function dump()
    {
        foreach ($this->stack as $trace) {
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
     * @return ProfilerMapCollection
     * @throws Exception
     */
    public function map()
    {
        if (!$this->storage) {
            throw new Exception('Не задано хранилище');
        }

        $map  = new ProfilerMapCollection();
        $list = $this->storage->getList();
        foreach ($list as $trace) {
            /** @var ProfilerStackModel $trace */
            $map->append($trace);
        }

        return $map;
    }

    /**
     * Сгенерировать карту вызовов
     */
    public function generateHtmlMapOutput()
    {
        if (!$this->mapOutputLocation) {
            throw new Exception('Не задана директория для генерации карты профайлера');
        }

        $map    = Map::unique($this->map->getList());
        $loader = new \Twig_Loader_Filesystem(__DIR__ . self::TEMPLATES_DIR);
        $twig   = new \Twig_Environment($loader);

        $output = $twig->render('profiler_map.twig', array(
            'map'     => Map::toArray($map),
            'cssFile' => __DIR__ . self::PUBLIC_DIR  . '/css/main.css',
            'error'   => $this->validateMap($map),
        ));

        file_put_contents($this->mapOutputLocation . '/profiler_map.html', $output);
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
     * Проверка карты
     *
     * @param array $map
     * @return null|array
     *
     * @todo $exception переделать на исключение
     */
    private function validateMap(array $map)
    {
        $hashWrongList = array();

        try {
            foreach ($map as $requests) {
                foreach ($requests as $hash => $traceHashList) {
                    $this->validateTraceHashList($traceHashList);
                    $hashWrongList[$hash] = $this->getWrongOrderCanContainsMap($traceHashList);
                }
            }

            $this->validateWrongKeysOrder($hashWrongList);

            return null;

        } catch (Exception $e) {
            $exception = null;

            if ($e->getProfilerStackModel()) {
                foreach ($map as $requests) {
                    foreach ($requests as $traceHashList) {
                        foreach ($traceHashList as $num => $trace) {
                            /** @var ProfilerStackModel $trace */
                            if ($e->getProfilerStackModel() === $trace) {
                                $exception = array(
                                    'requestHash' => $trace->getRequestHash(),
                                    'type'        => 'warning',
                                    'position'    => $num,
                                    'message'     => $e->getMessage()
                                );
                            }
                        }
                    }
                }
            }

            return $exception;
        }
    }

    /**
     * Проверка последовательности вызова блокировок для хеша
     *
     *  - Проверка последовательности вызова блокировок по ключу для хеша
     *  - Проверка перехлестных вызовов блокировок
     *
     * При возникновении ошибок возвращает исключение
     *
     * @param array $traceList
     */
    private function validateTraceHashList(array $traceList)
    {
        $this->validateHashKeysActionsOrder($traceList);
        $this->validateCrossOrder($traceList);
    }

    /**
     * Проверка последовательности вызова блокировок по ключу для хеша
     * Если хотя бы один ключ вызван с неправильной последовательностью, то функция возвращает исключение
     *
     * @param array $traceList
     */
    private function validateHashKeysActionsOrder(array $traceList)
    {
        $map = array();
        foreach ($traceList as $pos => $trace) {
            /** @var ProfilerStackModel $trace */
            $map[$trace->getKey()][$pos] = $trace;
        }
        foreach ($map as $actions) {
            $this->validateKeyHashActionsOrder($actions);
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
     * @param array $keyTraceList
     * @throws Exception
     */
    private function validateKeyHashActionsOrder(array $keyTraceList)
    {
        $wasGet     = false;
        $wasAcquire = false;

        foreach ($keyTraceList as $trace) {
            /** @var ProfilerStackModel $trace */
            if (!isset($listKey) && !isset($requestHash)) {
                $listKey     = $trace->getKey();
                $requestHash = $trace->getRequestHash();
            }
            if ($listKey !== $trace->getKey() || $requestHash !== $trace->getRequestHash()) {
                throw new Exception('Список вызова блокировок должны быть для одного ключа и хеша');
            }

            switch ($trace->getAction()) {
                case Mutex::ACTION_GET:
                    if ($wasGet === true) {
                        throw $this->getTraceModelException(
                            'Повторное получение указателя блокировки по ключу `%s`',
                            $trace
                        );
                    } else {
                        $wasGet = true;
                    }

                    break;
                case Mutex::ACTION_ACQUIRE:
                    if ($wasGet !== true) {
                        throw $this->getTraceModelException(
                            'Не найдено получения указателя блокировки по ключу `%s`',
                            $trace
                        );
                    } else {
                        if ($wasAcquire === true) {
                            throw $this->getTraceModelException(
                                'Повторная установка блокировки по ключу `%s`',
                                $trace
                            );
                        } else {
                            $wasAcquire = true;
                        }
                    }

                    break;
                case Mutex::ACTION_RELEASE:
                    if ($wasAcquire !== true) {
                        throw $this->getTraceModelException(
                            'Не найдена установка блокировки по ключу `%s`',
                            $trace
                        );
                    } else {
                        $wasGet     = false;
                        $wasAcquire = false;
                    }

                    break;
            }
        }
    }

    /**
     * Проверка перехлестных вызовов блокировок
     *
     * Исключение ситуаций типа:
     *  - get A
     *  - get B
     *  - acquire A
     *  - acquire B
     *  - release A
     *  - release B
     *
     * Схема вызова:
     *
     * <A>
     *  <B>
     *  </A>
     * </B>
     *
     * Должно быть:
     *
     * <A>
     *  <B>
     *  </B>
     * </A>
     *
     * @param $mapHashList
     * @throws Exception
     */
    private function validateCrossOrder(array $mapHashList)
    {
        $acquired  = $this->getHashCrossOrderMap($mapHashList);
        $exception = null;

        /** @var ProfilerStackModel $trace */
        foreach ($mapHashList as $trace) {
            /** @var ProfilerCrossOrder $keyCrossOrderModel */
            $keyCrossOrderModel = $acquired[$trace->getKey()];

            switch ($trace->getAction()) {
                case Mutex::ACTION_ACQUIRE:
                    $keyCrossOrderModel->acquire();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerCrossOrder $otherKeyCrossOrderModel */
                        if ($otherKeyCrossOrderModel->isAcquired()) {
                            if ($otherKeyCrossOrderModel->getKey() !== $trace->getKey()) {
                                $otherKeyCrossOrderModel->addContainKey($trace->getKey());
                            }
                        }
                    }

                    break;
                case Mutex::ACTION_RELEASE:
                    $keyCrossOrderModel->release();

                    if ($keyCrossOrderModel->hasContainKeys()) {
                        throw $this->getTraceModelException(
                            'Не возможно снять блокировку с ключа `%s` пока вложенные блокировки еще заняты',
                            $trace
                        );
                    }

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerCrossOrder $otherKeyCrossOrderModel */
                        $otherKeyCrossOrderModel->removeContainKey($trace->getKey());
                    }

                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Проверка правильного вызова ключей
     *
     * Исключение ситуаций типа (схема вызова):
     *
     * <A>
     *  <B>
     *  <B>
     * </A>
     *
     * Должно быть:
     *
     * <B>
     *  <A>
     *  </A>
     * </B>
     *
     * @param array $hashWrongList
     * @throws Exception
     */
    private function validateWrongKeysOrder(array $hashWrongList)
    {
        $keys = array();
        foreach ($hashWrongList as $wrongOrderHash) {
            foreach ($wrongOrderHash as $wrongOrderModel) {
                /** @var ProfilerWrongOrder $wrongOrderModel */
                $keys[] = $wrongOrderModel;
            }
        }
        foreach ($keys as $hashKey) {
            /** @var ProfilerWrongOrder $hashKey */
            foreach ($hashKey->canContainKeys() as $containsKeyName) {
                foreach ($keys as $compareHashKey) {
                    /** @var ProfilerWrongOrder $compareHashKey */
                    if ($compareHashKey->getKey() === $containsKeyName) {
                        if ($compareHashKey->canContainKey($hashKey->getKey())) {
                            throw $this->getTraceModelException(
                                'Неправильная последовательность вызовов с ключем `%s`',
                                $hashKey->getTrace()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Возвращает какие вложенные ключи может хранить в себе ключ
     *
     * @param array $mapHashList
     * @return array
     */
    private function getWrongOrderCanContainsMap(array $mapHashList)
    {
        $acquired  = $this->getHashWrongOrderMap($mapHashList);
        $exception = null;

        /** @var ProfilerStackModel $trace */
        foreach ($mapHashList as $trace) {
            /** @var ProfilerWrongOrder $keyCrossOrderModel */
            $keyCrossOrderModel = $acquired[$trace->getKey()];

            switch ($trace->getAction()) {
                case Mutex::ACTION_ACQUIRE:
                    $keyCrossOrderModel->acquire();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerWrongOrder $otherKeyCrossOrderModel */
                        if ($otherKeyCrossOrderModel->isAcquired()) {
                            if ($otherKeyCrossOrderModel->getKey() !== $trace->getKey()) {
                                $otherKeyCrossOrderModel->addContainKey($trace->getKey());
                            }
                        }
                    }

                    break;
                case Mutex::ACTION_RELEASE:
                    $keyCrossOrderModel->release();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerWrongOrder $otherKeyCrossOrderModel */
                        $otherKeyCrossOrderModel->removeContainKey($trace->getKey());
                    }

                    break;
                default:
                    break;
            }
        }

        return $acquired;
    }

    /**
     * Карта перекрестных связей для хеша вызовов
     *
     * @param array $mapHashList
     * @return array
     */
    private function getHashCrossOrderMap(array $mapHashList)
    {
        $acquired = array();
        foreach ($mapHashList as $trace) {
            /** @var ProfilerStackModel $trace */
            $acquired[$trace->getKey()] = new ProfilerCrossOrder($trace->getKey());
        }

        return $acquired;
    }

    /**
     * Карта неправильной последовательности для хеша вызовов
     *
     * @param array $mapHashList
     * @return array
     */
    private function getHashWrongOrderMap(array $mapHashList)
    {
        $acquired = array();
        foreach ($mapHashList as $trace) {
            /** @var ProfilerStackModel $trace */
            if (!isset($acquired[$trace->getKey()])) {
                $acquired[$trace->getKey()] = new ProfilerWrongOrder($trace);
            }
        }

        return $acquired;
    }

    /**
     * Исключение с моделью стека вызова профайлера
     *
     * @param string             $message
     * @param ProfilerStackModel $trace
     *
     * @return Exception
     */
    private function getTraceModelException($message, ProfilerStackModel $trace)
    {
        $exception = new Exception(sprintf($message, $trace->getKey()));
        $exception->setProfilerStackModel($trace);

        return $exception;
    }
} 