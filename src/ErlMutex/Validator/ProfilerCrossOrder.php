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

namespace ErlMutex\Validator;

use ErlMutex\Model\ProfilerMapCollection;
use ErlMutex\Model\ProfilerCrossOrder as ProfilerCrossOrderModel;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use ErlMutex\Model\ProfilerStackCollection;
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
     * @param ProfilerMapCollection $mapCollection
     * @return mixed
     */
    public function validate(ProfilerMapCollection $mapCollection)
    {
        /** @var ProfilerStackCollection $requestCollection */
        foreach ($mapCollection as $requestCollection) {
            $this->validateCrossOrder($requestCollection);
        }
    }

    /**
     * Проверка перехлестных вызовов блокировок
     *
     * @param ProfilerStackCollection $requestCollection
     * @throws Exception
     */
    private function validateCrossOrder(ProfilerStackCollection $requestCollection)
    {
        $acquired  = $this->getHashCrossOrderMap($requestCollection);
        $exception = null;

        /** @var ProfilerStackModel $trace */
        foreach ($requestCollection as $trace) {
            /** @var ProfilerCrossOrderModel $keyCrossOrderModel */
            $keyCrossOrderModel = $acquired[$trace->getKey()];

            switch ($trace->getAction()) {
                case Mutex::ACTION_ACQUIRE:
                    $keyCrossOrderModel->acquire();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerCrossOrderModel $otherKeyCrossOrderModel */
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
                        /** @var ProfilerCrossOrderModel $otherKeyCrossOrderModel */
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
     * @param ProfilerStackCollection $mapHashList
     * @return array
     */
    private function getHashCrossOrderMap(ProfilerStackCollection $mapHashList)
    {
        $acquired = array();
        foreach ($mapHashList as $trace) {
            /** @var ProfilerStackModel $trace */
            $acquired[$trace->getKey()] = new ProfilerCrossOrderModel($trace->getKey());
        }

        return $acquired;
    }
}