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

use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use ErlMutex\Model\ProfilerCrossOrder as ProfilerCrossOrderModel;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Коллекция последовательности вызова блокировок (деление по ключу)
 *
 * Class ProfilerCrossOrderCollection
 * @package ErlMutex\Model
 */
class ProfilerCrossOrderCollection extends AbstractCollection
{
    /**
     * Добавить модель
     *
     * @param ProfilerStack $trace
     * @return $this
     */
    public function append(ProfilerStackModel $trace)
    {
        if (!$this->hasModelByTrace($trace)) {
            $this->collection[] = new ProfilerCrossOrderModel($trace->getKey());
        }

        return $this;
    }

    /**
     * Проверить есть ли такая модель по модели запроса
     *
     * @param ProfilerStack $trace
     * @return bool
     */
    public function hasModelByTrace(ProfilerStackModel $trace)
    {
        /** @var ProfilerCrossOrderModel $crossOrderModel */
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
     * @param ProfilerStack $trace
     * @return ProfilerCrossOrder
     * @throws \ErlMutex\Exception\ProfilerException
     */
    public function getModelByTrace(ProfilerStackModel $trace)
    {
        /** @var ProfilerCrossOrderModel $crossOrderModel */
        foreach ($this->collection as $crossOrderModel) {
            if ($crossOrderModel->getKey() === $trace->getKey()) {
                return $crossOrderModel;
            }
        }

        throw new Exception('Модель не найдена');
    }
}