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

namespace Mutex\Model;

use DateTime;

/**
 * Class ProfileStackModel
 * @package Mutex\Model
 */
class ProfileStackModel
{
    /**
     * @var string
     */
    private $_filename;

    /**
     * @var string
     */
    private $_method;

    /**
     * @var int
     */
    private $_line;

    /**
     * @var string
     */
    private $_key;

    /**
     * @var mixed
     */
    private $_response;

    /**
     * @var DateTime
     */
    private $_dateTime;

    /**
     * @var null|string
     */
    private $_stackTrace;

    /**
     * Constructor
     *
     * @param string   $filename
     * @param string   $method
     * @param int      $line
     * @param string   $key
     * @param mixed    $response
     * @param DateTime $dateTime
     * @param string   $stackTrace
     */
    public function __construct($filename, $method, $line, $key, $response, DateTime $dateTime, $stackTrace=null)
    {
        $this->_filename   = $filename;
        $this->_method     = $method;
        $this->_line       = $line;
        $this->_key        = $key;
        $this->_response   = $response;
        $this->_dateTime   = $dateTime;
        $this->_stackTrace = $stackTrace;
    }
}