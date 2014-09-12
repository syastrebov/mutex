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

namespace ErlMutex;

use ErlMutex\Entity\Profiler\MapCollection;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Анализатор правильности вызова блокировок
 *
 * Interface ProfilerValidatorInterface
 * @package ErlMutex
 */
interface ProfilerValidatorInterface
{
    /**
     * Анализировать карту вызова блокировок
     *
     * @param MapCollection $mapCollection
     * @throws Exception
     */
    public function validate(MapCollection $mapCollection);
} 