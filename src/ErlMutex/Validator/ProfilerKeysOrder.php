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
use ErlMutex\Model\ProfilerWrongOrder as ProfilerWrongOrderModel;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use ErlMutex\Model\ProfilerStackCollection;
use ErlMutex\Service\Mutex;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Проверка правильного вызова ключей
 *
 * Исключение ситуаций типа (схема вызова):
 *
 * <A>
 *  <B>
 *  <B>
 * </A>
 *
 * Должно быть:
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
     * @return mixed
     */
    public function validate(ProfilerMapCollection $mapCollection)
    {
        $hashWrongList = array();
        /** @var ProfilerStackCollection $requestCollection */
        foreach ($mapCollection as $requestCollection) {
            $hashWrongList[$requestCollection->getRequestHash()] = $this->getWrongOrderCanContainsMap($requestCollection);
        }

        $this->validateWrongKeysOrder($hashWrongList);
    }

    /**
     * Проверка правильного вызова ключей
     *
     * @param array $hashWrongList
     * @throws Exception
     */
    private function validateWrongKeysOrder(array $hashWrongList)
    {
        $keys = array();
        foreach ($hashWrongList as $wrongOrderHash) {
            foreach ($wrongOrderHash as $wrongOrderModel) {
                /** @var ProfilerWrongOrderModel $wrongOrderModel */
                $keys[] = $wrongOrderModel;
            }
        }
        foreach ($keys as $hashKey) {
            /** @var ProfilerWrongOrderModel $hashKey */
            foreach ($hashKey->canContainKeys() as $containsKeyName) {
                foreach ($keys as $compareHashKey) {
                    /** @var ProfilerWrongOrderModel $compareHashKey */
                    if ($compareHashKey->getKey() === $containsKeyName) {
                        if ($compareHashKey->canContainKey($hashKey->getKey())) {
                            throw $this->getTraceModelException(
                                'Неправильная последовательность вызовов с ключем `%s`',
                                $hashKey->getTrace()
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
     * @param ProfilerStackCollection $mapHashList
     * @return array
     */
    private function getWrongOrderCanContainsMap(ProfilerStackCollection $mapHashList)
    {
        $acquired  = $this->getHashWrongOrderMap($mapHashList);
        $exception = null;

        /** @var ProfilerStackModel $trace */
        foreach ($mapHashList as $trace) {
            /** @var ProfilerWrongOrderModel $keyCrossOrderModel */
            $keyCrossOrderModel = $acquired[$trace->getKey()];

            switch ($trace->getAction()) {
                case Mutex::ACTION_ACQUIRE:
                    $keyCrossOrderModel->acquire();

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
                    $keyCrossOrderModel->release();

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
     * @param ProfilerStackCollection $mapHashList
     * @return array
     */
    private function getHashWrongOrderMap(ProfilerStackCollection $mapHashList)
    {
        $acquired = array();
        foreach ($mapHashList as $trace) {
            /** @var ProfilerStackModel $trace */
            if (!isset($acquired[$trace->getKey()])) {
                $acquired[$trace->getKey()] = new ProfilerWrongOrderModel($trace);
            }
        }

        return $acquired;
    }
}