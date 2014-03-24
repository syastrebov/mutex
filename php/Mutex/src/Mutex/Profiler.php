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

use DateTime;

/**
 * Class Profiler
 * @package Mutex
 */
class Profiler
{
    /**
     * @param string $str
     */
    public static function msg($str)
    {
        $time = new DateTime;
        echo sprintf("%s on %s\r\n", $str, $time->format('H:i:s'));
        flush();
    }
} 