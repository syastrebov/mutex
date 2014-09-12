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
use ErlMutex\Entity\Profiler\Stack as ProfilerStackEntity;
use ErlMutex\Entity\Profiler\CrossOrder as ProfilerCrossOrderEntity;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Коллекция последовательности вызова блокировок (деление по ключу)
 *
 * Class ProfilerCrossOrderCollection
 * @package ErlMutex\Entity
 */
class CrossOrderCollection extends AbstractCollection
{
    /**
     * Добавить модель
     *
     * @param ProfilerStackEntity $trace
     * @return $this
     */
    public function append(ProfilerStackEntity $trace)
    {
        if (!$this->hasModelByTrace($trace)) {
            $this->collection[] = new ProfilerCrossOrderEntity($trace->getKey());
        }

        return $this;
    }

    /**
     * Проверить есть ли такая модель по модели запроса
     *
     * @param ProfilerStackEntity $trace
     * @return bool
     */
    public function hasModelByTrace(ProfilerStackEntity $trace)
    {
        /** @var ProfilerCrossOrderEntity $crossOrderModel */
        foreach ($this->collection as $crossOrderModel) {
            if ($crossOrderModel->getKey() === $trace->getKey()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить модель по модели запроса
     *
     * @param ProfilerStackEntity $trace
     * @return CrossOrder
     * @throws \ErlMutex\Exception\ProfilerException
     */
    public function getModelByTrace(ProfilerStackEntity $trace)
    {
        /** @var ProfilerCrossOrderEntity $crossOrderModel */
        foreach ($this->collection as $crossOrderModel) {
            if ($crossOrderModel->getKey() === $trace->getKey()) {
                return $crossOrderModel;
            }
        }

        throw new Exception('Модель не найдена');
    }
}