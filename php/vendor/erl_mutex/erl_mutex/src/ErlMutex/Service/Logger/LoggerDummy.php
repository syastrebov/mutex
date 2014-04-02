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

namespace ErlMutex\Service\Logger;

use ErlMutex\LoggerInterface;

/**
 * Заглушка для логирования
 *
 * Class Logger
 * @package erl_mutex\Service
 */
class LoggerDummy implements LoggerInterface
{
    /**
     * Добавить запись в лог
     *
     * @param string $data
     * @return mixed
     */
    public function insert($data)
    {

    }
}