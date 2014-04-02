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

namespace ErlMutex\Service\Logger;

use ErlMutex\LoggerInterface;

/**
 * Заглушка для логирования
 *
 * Class Logger
 * @package erl\Service
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