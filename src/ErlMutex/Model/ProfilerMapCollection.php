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

use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Class ProfilerMapCollection
 * @package ErlMutex\Model
 */
class ProfilerMapCollection extends AbstractCollection
{
    /**
     * Добавить запрос в коллекцию
     *
     * @param ProfilerStackCollection $stackCollection
     * @return $this
     */
    public function ignoreAppend(ProfilerStackCollection $stackCollection)
    {
        if (!$this->hasCollection($stackCollection)) {
            $this->collection[] = $stackCollection;
        }

        return $this;
    }

    /**
     * Есть ли уже такая коллекция
     *
     * @param ProfilerStackCollection $stackCollection
     * @return bool
     */
    public function hasCollection(ProfilerStackCollection $stackCollection)
    {
        foreach ($this->collection as $existStackCollection) {
            /** @var ProfilerStackCollection $existStackCollection */
            if ($existStackCollection->getRequestHash() === $stackCollection->getRequestHash()) {
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
     * @todo Реализовать метод
     */
    public function getUniqueCollections()
    {
        return new ProfilerMapCollection();
    }
} 