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

namespace Mutex\Service\Logger;

use Mutex\LoggerInterface;

/**
 * Заглушка для логирования
 *
 * Class Logger
 * @package Mutex\Service
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