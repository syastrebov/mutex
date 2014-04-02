<?php

/**
 * PHP-Erlang erl_mutex
 * Сервис блокировок для обработки критических секций
 *
 * @category erl_mutex
 * @package  erl_mutex
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/erl_mutex
 */

namespace ErlMutex;

/**
 * Логирование исключительных ситуаций вызова блокировок
 *
 * Interface LoggerInterface
 * @package erl_mutex
 */
interface LoggerInterface
{
    /**
     * Добавить запись в лог
     *
     * @param string $data
     * @return mixed
     */
    public function insert($data);
} 