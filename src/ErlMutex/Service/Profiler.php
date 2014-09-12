<?php

/**
 * PHP-Erlang erl
 * Сервис блокировок для обработки критических секций
 *
 * @category erl
 * @package  erl
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/mutex-php
 */

namespace ErlMutex\Service;

use ErlMutex\Exception\ProfilerException as Exception;
use ErlMutex\Entity\Profiler\MapCollection;
use ErlMutex\Entity\Profiler\Stack as ProfilerStackModel;
use ErlMutex\Entity\Profiler\StackCollection;
use ErlMutex\ProfilerStorageInterface;
use DateTime;
use ErlMutex\ProfilerValidatorInterface;
use ErlMutex\Validator\ProfilerCollection as ProfilerValidatorCollection;

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
     * @var StackCollection
     */
    private $stack;

    /**
     * Хранилище истории блокировок
     *
     * @var ProfilerStorageInterface
     */
    private $storage;

    /**
     * Путь к директории сохранения карты
     *
     * @var string
     */
    private $mapOutputLocation;

    /**
     * Валидаторы для проверки запросов
     *
     * @var ProfilerValidatorCollection
     */
    private $validators;

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
        $this->stack        = new StackCollection($this->getRequestHash());
        $this->validators   = ProfilerValidatorCollection::getInstance();
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
            $this->stack->append($model);
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
     * Возвращается в формате:
     *
     * - requestHash 1
     *      * trace 1
     *      * trace 2
     *      ...
     * - requestHash 2
     *      * trace 1
     *      * trace 2
     *      ...
     *
     * @return MapCollection
     * @throws Exception
     */
    public function getMap()
    {
        if (!$this->storage) {
            throw new Exception('Не задано хранилище');
        }

        $map  = new MapCollection();
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

        $map    = $this->getMap()->getUniqueCollections();
        $loader = new \Twig_Loader_Filesystem(__DIR__ . self::TEMPLATES_DIR);
        $twig   = new \Twig_Environment($loader);
        $error  = null;

        try {
            $this->validate($map);
        } catch (Exception $e) {
            $error = $e->getDescription();
        }

        $output = $twig->render('profiler_map.twig', array(
            'map'     => $map->asArrayByRequestUri(),
            'cssFile' => __DIR__ . self::PUBLIC_DIR  . '/css/main.css',
            'error'   => $error,
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
     * @param MapCollection $map
     * @throws Exception
     */
    public function validate(MapCollection $map)
    {
        try {
            foreach ($this->validators as $validator) {
                /** @var ProfilerValidatorInterface $validator */
                $validator->validate($map);
            }

        } catch (Exception $e) {
            if ($e->getProfilerStackModel()) {
                foreach ($map as $requestCollection) {
                    foreach ($requestCollection as $num => $trace) {
                        /** @var ProfilerStackModel $trace */
                        if ($e->getProfilerStackModel() === $trace) {
                            $e->setDescription(array(
                                'requestHash' => $trace->getRequestHash(),
                                'type'        => 'warning',
                                'position'    => $num,
                                'message'     => $e->getMessage()
                            ));
                        }
                    }
                }
            }

            throw $e;
        }
    }
} 