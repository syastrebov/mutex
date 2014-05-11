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

use ErlMutex\ProfilerStorageInterface;
use ErlMutex\Exception\ProfilerException as Exception;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;

/**
 * Карта профайлера
 *
 * Class Map
 * @package ErlMutex\Service
 */
class Map
{
    /**
     * Хранилище истории блокировок
     *
     * @var ProfilerStorageInterface
     */
    private $storage;

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
     * Получить список запросов
     *
     * @return array
     * @throws \ErlMutex\Exception\ProfilerException
     */
    public function getList()
    {
        if (!$this->storage) {
            throw new Exception('Не задано хранилище');
        }

        $result = array();
        $list   = $this->storage->getList();

        foreach ($list as $trace) {
            /** @var ProfilerStackModel $trace */
            if (!isset($result[$trace->getRequestUri()][$trace->getRequestHash()])) {
                $result[$trace->getRequestUri()][$trace->getRequestHash()] = array();
            }

            $result[$trace->getRequestUri()][$trace->getRequestHash()][] = $trace;
        }

        return $result;
    }

    /**
     * Преобразовать карту запросов в массив данных
     * Применяется для вывода
     *
     * @param array $map
     * @return array
     * @throws Exception
     */
    public static function toArray(array $map)
    {
        foreach ($map as &$requests) {
            foreach ($requests as &$traceHashList) {
                $traceHashList = self::traceHashListToArray($traceHashList);
            }
        }

        return $map;
    }

    /**
     * Убрать дубликаты вызовов
     *
     * @param array $map
     * @return array
     */
    public static function unique(array $map)
    {
        foreach ($map as &$requests) {
            $uniqueTraceHashList = array();

            foreach ($requests as $requestHash => &$traceHashList) {
                $traceListHash = md5(serialize(self::traceHashListToArray($traceHashList)));
                if (!in_array($traceListHash, $uniqueTraceHashList)) {
                    $uniqueTraceHashList[] = $traceListHash;
                } else {
                    unset($requests[$requestHash]);
                }
            }
        }

        return $map;
    }

    /**
     * Преобразовать последовательность из объектов в массивы
     *
     * @param array $traceHashList
     * @return array
     * @throws \ErlMutex\Exception\ProfilerException
     */
    private static function traceHashListToArray(array $traceHashList)
    {
        foreach ($traceHashList as &$trace) {
            if (!$trace instanceof ProfilerStackModel) {
                throw new Exception('Передана неправильная карта блокировок');
            }

            $trace = $trace->asArray();
        }

        return $traceHashList;
    }
}