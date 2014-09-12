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

namespace ErlMutex\Validator;

use ErlMutex\Entity\Profiler\CrossOrderCollection;
use ErlMutex\Entity\Profiler\MapCollection;
use ErlMutex\Entity\Profiler\CrossOrder as ProfilerCrossOrderEntity;
use ErlMutex\Entity\Profiler\Stack as ProfilerStackModel;
use ErlMutex\Entity\Profiler\StackCollection;
use ErlMutex\Service\Mutex;
use ErlMutex\Exception\ProfilerException as Exception;

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
 * Class ProfilerCrossOrder
 * @package ErlMutex\Validator
 */
class ProfilerCrossOrder extends ProfilerAbstract
{
    /**
     * Анализировать карту вызова блокировок
     *
     * @param MapCollection $mapCollection
     * @throws Exception
     */
    public function validate(MapCollection $mapCollection)
    {
        /** @var StackCollection $requestCollection */
        foreach ($mapCollection as $requestCollection) {
            $this->validateCrossOrder($requestCollection);
        }
    }

    /**
     * Проверка перехлестных вызовов блокировок
     *
     * @param StackCollection $requestCollection
     * @throws Exception
     */
    private function validateCrossOrder(StackCollection $requestCollection)
    {
        $acquired = $this->getHashCrossOrderMap($requestCollection);

        /** @var ProfilerStackModel $trace */
        foreach ($requestCollection as $trace) {
            $keyCrossOrderModel = $acquired->getModelByTrace($trace);

            switch ($trace->getAction()) {
                case Mutex::ACTION_ACQUIRE:
                    $keyCrossOrderModel->acquire();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerCrossOrderEntity $otherKeyCrossOrderModel */
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
                        /** @var ProfilerCrossOrderEntity $otherKeyCrossOrderModel */
                        $otherKeyCrossOrderModel->removeContainKey($trace->getKey());
                    }

                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Карта перекрестных связей для хеша вызовов
     *
     * @param StackCollection $requestCollection
     * @return CrossOrderCollection
     */
    private function getHashCrossOrderMap(StackCollection $requestCollection)
    {
        $collection = new CrossOrderCollection();
        foreach ($requestCollection as $trace) {
            /** @var ProfilerStackModel $trace */
            $collection->append($trace);
        }

        return $collection;
    }
}