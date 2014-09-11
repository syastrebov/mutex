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

namespace ErlMutex\Model;

use ErlMutex\Exception\ProfilerException as Exception;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;

/**
 * Карта коллекций запросов профайлера
 *
 * Class ProfilerMapCollection
 * @package ErlMutex\Model
 */
class ProfilerMapCollection extends AbstractCollection
{
    /**
     * Добавить запрос в коллекцию
     *
     * @param ProfilerStackModel $trace
     * @return $this
     */
    public function append(ProfilerStackModel $trace)
    {
        if (!$this->hasCollection($trace->getRequestHash())) {
            $this->collection[] = new ProfilerStackCollection($trace->getRequestHash());
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
            /** @var ProfilerStackCollection $existStackCollection */
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
     * @return ProfilerStackCollection
     * @throws Exception
     */
    public function getCollectionByRequestHash($requestHash)
    {
        foreach ($this->collection as $existStackCollection) {
            /** @var ProfilerStackCollection $existStackCollection */
            if ($existStackCollection->getRequestHash() === $requestHash) {
                return $existStackCollection;
            }
        }

        throw new Exception('Коллекция не найдена');
    }

    /**
     * Получить уникальные коллекции
     *
     * @return ProfilerMapCollection
     */
    public function getUniqueCollections()
    {
        $uniqueCollection = new ProfilerMapCollection();
        /** @var ProfilerStackCollection $requestCollection */
        foreach ($this->collection as $requestCollection) {
            $exist = false;
            /** @var ProfilerStackCollection $existRequestCollection */
            foreach ($uniqueCollection as $existRequestCollection) {
                if ($requestCollection->getModelHash() === $existRequestCollection->getModelHash()) {
                    $exist = true;
                }
            }
            if (!$exist) {
                /** @var ProfilerStackModel $trace */
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
            /** @var ProfilerStackCollection $existStackCollection */
            $result[$existStackCollection->getRequestUri()][] = $existStackCollection->asArray();
        }

        return $result;
    }
}