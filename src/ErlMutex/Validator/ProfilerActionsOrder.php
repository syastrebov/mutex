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
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;
use ErlMutex\Model\ProfilerStackCollection;
use ErlMutex\Service\Mutex;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Проверка последовательности вызова блокировок по ключу
 *
 * Правильная последовательность:
 *  - get(Key)
 *  - acquire(Key)
 *  - release(Key)
 * Если последовательность не совпадает, возвращает исключение
 *
 * Class ProfilerActionsOrder
 * @package ErlMutex\Validator
 */
class ProfilerActionsOrder extends ProfilerAbstract
{
    /**
     * Анализировать карту вызова блокировок
     *
     * @param ProfilerMapCollection $mapCollection
     * @return mixed
     */
    public function validate(ProfilerMapCollection $mapCollection)
    {
        foreach ($mapCollection as $requestCollection) {
            /** @var ProfilerStackCollection $requestCollection */
            $keys = $requestCollection->getKeys();

            foreach ($keys as $key) {
                $this->validateKeyOrder($key, $requestCollection);
            }
        }
    }

    /**
     * Проверка последовательности вызова блокировок по ключу
     * Если последовательность не совпадает, то функция возвращает исключение
     *
     * @param string                  $key
     * @param ProfilerStackCollection $requestCollection
     *
     * @throws Exception
     */
    private function validateKeyOrder($key, ProfilerStackCollection $requestCollection)
    {
        $wasGet     = false;
        $wasAcquire = false;

        foreach ($requestCollection as $trace) {
            /** @var ProfilerStackModel $trace */
            if ($trace->getKey() !== $key) {
                continue;
            }
            if (!isset($listKey) && !isset($requestHash)) {
                $listKey     = $trace->getKey();
                $requestHash = $trace->getRequestHash();
            }

            switch ($trace->getAction()) {
                case Mutex::ACTION_GET:
                    if ($wasGet === true) {
                        throw $this->getTraceModelException(
                            'Повторное получение указателя блокировки по ключу `%s`',
                            $trace
                        );
                    } else {
                        $wasGet = true;
                    }

                    break;
                case Mutex::ACTION_ACQUIRE:
                    if ($wasGet !== true) {
                        throw $this->getTraceModelException(
                            'Не найдено получения указателя блокировки по ключу `%s`',
                            $trace
                        );
                    } else {
                        if ($wasAcquire === true) {
                            throw $this->getTraceModelException(
                                'Повторная установка блокировки по ключу `%s`',
                                $trace
                            );
                        } else {
                            $wasAcquire = true;
                        }
                    }

                    break;
                case Mutex::ACTION_RELEASE:
                    if ($wasAcquire !== true) {
                        throw $this->getTraceModelException(
                            'Не найдена установка блокировки по ключу `%s`',
                            $trace
                        );
                    } else {
                        $wasGet     = false;
                        $wasAcquire = false;
                    }

                    break;
            }
        }
    }
}