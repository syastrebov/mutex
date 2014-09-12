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
use ErlMutex\Entity\Profiler\WrongOrder as ProfilerWrongOrderEntity;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Коллекция последовательности вызова ключей
 *
 * Class ProfilerWrongOrderCollection
 * @package ErlMutex\Entity
 */
class WrongOrderCollection extends AbstractCollection
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
            $this->collection[] = new ProfilerWrongOrderEntity($trace);
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
        /** @var ProfilerWrongOrderEntity $wrongOrderModel */
        foreach ($this->collection as $wrongOrderModel) {
            if ($wrongOrderModel->getKey() === $trace->getKey()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить модель по модели запроса
     *
     * @param ProfilerStackEntity $trace
     * @return ProfilerWrongOrderEntity
     * @throws \ErlMutex\Exception\ProfilerException
     */
    public function getModelByTrace(ProfilerStackEntity $trace)
    {
        /** @var ProfilerWrongOrderEntity $wrongOrderModel */
        foreach ($this->collection as $wrongOrderModel) {
            if ($wrongOrderModel->getKey() === $trace->getKey()) {
                return $wrongOrderModel;
            }
        }

        throw new Exception('Модель не найдена');
    }
}