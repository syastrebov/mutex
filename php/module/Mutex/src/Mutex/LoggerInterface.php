<?php

/**
 * PHP-Erlang Mutex
 * Сервис блокировок для обработки критических секций
 *
 * @category Mutex
 * @package  Mutex
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/mutex
 */

namespace Mutex;

/**
 * Логирование исключительных ситуаций вызова блокировок
 *
 * Interface LoggerInterface
 * @package Mutex
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