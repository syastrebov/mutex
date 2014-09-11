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

use ErlMutex\Model\ProfilerMapCollection;
use ErlMutex\Model\ProfilerWrongOrder as ProfilerWrongOrderModel;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use ErlMutex\Model\ProfilerStackCollection;
use ErlMutex\Model\ProfilerWrongOrderCollection;
use ErlMutex\Service\Mutex;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Проверка правильного вызова ключей
 *
 * Исключение ситуаций типа (схема вызова).
 * В первом вызове блокировка "B" вложена в "A", во втором вызове - наоборот "A" в "B":
 *
 * <A>
 *  <B>
 *  </B>
 * </A>
 *
 * <B>
 *  <A>
 *  </A>
 * </B>
 *
 * Class ProfilerKeysOrder
 * @package ErlMutex\Validator
 */
class ProfilerKeysOrder extends ProfilerAbstract
{
    /**
     * Анализировать карту вызова блокировок
     *
     * @param ProfilerMapCollection $mapCollection
     * @throws Exception
     */
    public function validate(ProfilerMapCollection $mapCollection)
    {
        $keysOrderMap = array();
        /** @var ProfilerStackCollection $requestCollection */
        foreach ($mapCollection as $requestCollection) {
            $requestKeysOrderMap = $this->getWrongOrderCanContainsMap($requestCollection);
            foreach ($requestKeysOrderMap as $wrongOrderModel) {
                /** @var ProfilerWrongOrderModel $wrongOrderModel */
                $keysOrderMap[] = $wrongOrderModel;
            }
        }
        foreach ($keysOrderMap as $keyWrongOrderModel) {
            /** @var ProfilerWrongOrderModel $keyWrongOrderModel */
            foreach ($keyWrongOrderModel->canContainKeys() as $containsKeyName) {
                foreach ($keysOrderMap as $compareKeyWrongOrderModel) {
                    /** @var ProfilerWrongOrderModel $compareKeyWrongOrderModel */
                    if ($compareKeyWrongOrderModel->getKey() === $containsKeyName) {
                        if ($compareKeyWrongOrderModel->canContainKey($keyWrongOrderModel->getKey())) {
                            throw $this->getTraceModelException(
                                'Неправильная последовательность вызовов с ключем `%s`',
                                $keyWrongOrderModel->getTrace()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Возвращает какие вложенные ключи может хранить в себе ключ
     *
     * @param ProfilerStackCollection $requestCollection
     * @return ProfilerWrongOrderCollection
     */
    private function getWrongOrderCanContainsMap(ProfilerStackCollection $requestCollection)
    {
        $acquired = $this->getWrongOrderCollection($requestCollection);

        /** @var ProfilerStackModel $trace */
        foreach ($requestCollection as $trace) {
            $keyWrongOrderModel = $acquired->getModelByTrace($trace);

            switch ($trace->getAction()) {
                case Mutex::ACTION_ACQUIRE:
                    $keyWrongOrderModel->acquire();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerWrongOrderModel $otherKeyCrossOrderModel */
                        if ($otherKeyCrossOrderModel->isAcquired()) {
                            if ($otherKeyCrossOrderModel->getKey() !== $trace->getKey()) {
                                $otherKeyCrossOrderModel->addContainKey($trace->getKey());
                            }
                        }
                    }

                    break;
                case Mutex::ACTION_RELEASE:
                    $keyWrongOrderModel->release();

                    foreach ($acquired as $otherKeyCrossOrderModel) {
                        /** @var ProfilerWrongOrderModel $otherKeyCrossOrderModel */
                        $otherKeyCrossOrderModel->removeContainKey($trace->getKey());
                    }

                    break;
                default:
                    break;
            }
        }

        return $acquired;
    }

    /**
     * Карта неправильной последовательности для хеша вызовов
     *
     * @param ProfilerStackCollection $requestCollection
     * @return ProfilerWrongOrderCollection
     */
    private function getWrongOrderCollection(ProfilerStackCollection $requestCollection)
    {
        $collection = new ProfilerWrongOrderCollection();
        foreach ($requestCollection as $trace) {
            /** @var ProfilerStackModel $trace */
            $collection->append($trace);
        }

        return $collection;
    }
}