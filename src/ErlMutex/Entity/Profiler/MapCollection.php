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

namespace ErlMutex\Entity\Profiler;

use ErlMutex\Entity\AbstractCollection;
use ErlMutex\Exception\ProfilerException as Exception;
use ErlMutex\Entity\Profiler\Stack as ProfilerStackEntity;

/**
 * Карта коллекций запросов профайлера
 *
 * Class ProfilerMapCollection
 * @package ErlMutex\Entity
 */
class MapCollection extends AbstractCollection
{
    /**
     * Добавить запрос в коллекцию
     *
     * @param ProfilerStackEntity $trace
     * @return $this
     */
    public function append(ProfilerStackEntity $trace)
    {
        if (!$this->hasCollection($trace->getRequestHash())) {
            $this->collection[] = new StackCollection($trace->getRequestHash());
        }

        $this->getCollectionByRequestHash($trace->getRequestHash())->append($trace);
        return $this;
    }

    /**
     * Есть ли уже такая коллекция
     *
     * @param string $requestHash
     * @return bool
     */
    public function hasCollection($requestHash)
    {
        foreach ($this->collection as $existStackCollection) {
            /** @var StackCollection $existStackCollection */
            if ($existStackCollection->getRequestHash() === $requestHash) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить ссылку на коллекцию по хешу запроса
     *
     * @param $requestHash
     * @return StackCollection
     * @throws Exception
     */
    public function getCollectionByRequestHash($requestHash)
    {
        foreach ($this->collection as $existStackCollection) {
            /** @var StackCollection $existStackCollection */
            if ($existStackCollection->getRequestHash() === $requestHash) {
                return $existStackCollection;
            }
        }

        throw new Exception('Коллекция не найдена');
    }

    /**
     * Получить уникальные коллекции
     *
     * @return MapCollection
     */
    public function getUniqueCollections()
    {
        $uniqueCollection = new MapCollection();
        /** @var StackCollection $requestCollection */
        foreach ($this->collection as $requestCollection) {
            $exist = false;
            /** @var StackCollection $existRequestCollection */
            foreach ($uniqueCollection as $existRequestCollection) {
                if ($requestCollection->getModelHash() === $existRequestCollection->getModelHash()) {
                    $exist = true;
                }
            }
            if (!$exist) {
                /** @var ProfilerStackEntity $trace */
                foreach ($requestCollection as $trace) {
                    $uniqueCollection->append($trace);
                }
            }

        }

        return $uniqueCollection;
    }

    /**
     * Преобразовать коллекцию в массив
     * Выполнить сортировку по урлу запроса
     *
     * @return array
     */
    public function asArrayByRequestUri()
    {
        $result = array();
        foreach ($this->collection as $existStackCollection) {
            /** @var StackCollection $existStackCollection */
            $result[$existStackCollection->getRequestUri()][] = $existStackCollection->asArray();
        }

        return $result;
    }
}