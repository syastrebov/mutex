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
     * @param string $filename
     * @param string $method
     * @param int    $line
     * @param string $key
     * @param string $stackTrace
     */
    public function callMethod($filename, $method, $line, $key, $stackTrace=null)
    {
        $this->_stack[] = new ProfileStackModel($filename, $method, $line, $key, $stackTrace);
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