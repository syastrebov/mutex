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

use ErlMutex\ProfilerValidatorInterface;
use ErlMutex\Exception\ProfilerException as Exception;
use ErlMutex\Entity\Profiler\Stack as ProfilerStackModel;

/**
 * Абстрактный класс валидатора профайлера
 *
 * Class ProfilerAbstract
 * @package ErlMutex\Validator
 */
abstract class ProfilerAbstract implements ProfilerValidatorInterface
{
    /**
     * Исключение с моделью стека вызова профайлера
     *
     * @param string             $message
     * @param ProfilerStackModel $trace
     *
     * @return Exception
     */
    protected function getTraceModelException($message, ProfilerStackModel $trace)
    {
        $exception = new Exception(sprintf($message, $trace->getKey()));
        $exception->setProfilerStackModel($trace);

        return $exception;
    }
} 