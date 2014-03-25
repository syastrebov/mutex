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

namespace Mutex\Service;

use Mutex\Model\ProfileStackModel;
use DateTime;

/**
 * Class Profiler
 * @package Mutex
 */
class Profiler
{
    /**
     * @var array
     */
    private $_stack = array();

    /**
     * Зафиксировать вызов метода
     *
     * @param string $key
     * @param mixed  $response
     */
    public function log($key, $response)
    {
        $stackTrace = debug_backtrace();
        if (is_array($stackTrace) && count($stackTrace) > 1) {
            $entry = $stackTrace[1];

            $this->_stack[] = new ProfileStackModel(
                isset($entry['file'])     ? $entry['file']     : null,
                isset($entry['function']) ? $entry['function'] : null,
                isset($entry['line'])     ? $entry['line']     : null,
                $key,
                $response,
                new DateTime(),
                $stackTrace
            );
        }
    }

    /**
     * Отладочное сообщение
     *
     * @param string $str
     */
    public static function debugMessage($str)
    {
        $time = new DateTime;
        echo sprintf("%s on %s\r\n", $str, $time->format('H:i:s'));
        flush();
    }
} 