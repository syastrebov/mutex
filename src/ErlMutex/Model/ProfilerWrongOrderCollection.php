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

use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use ErlMutex\Model\ProfilerWrongOrder as ProfilerWrongOrderModel;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Коллекция последовательности вызова ключей
 *
 * Class ProfilerWrongOrderCollection
 * @package ErlMutex\Model
 */
class ProfilerWrongOrderCollection extends AbstractCollection
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
            $this->collection[] = new ProfilerWrongOrderModel($trace);
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
        /** @var ProfilerWrongOrderModel $wrongOrderModel */
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
     * @param ProfilerStack $trace
     * @return ProfilerWrongOrderModel
     * @throws \ErlMutex\Exception\ProfilerException
     */
    public function getModelByTrace(ProfilerStackModel $trace)
    {
        /** @var ProfilerWrongOrderModel $wrongOrderModel */
        foreach ($this->collection as $wrongOrderModel) {
            if ($wrongOrderModel->getKey() === $trace->getKey()) {
                return $wrongOrderModel;
            }
        }

        throw new Exception('Модель не найдена');
    }
}