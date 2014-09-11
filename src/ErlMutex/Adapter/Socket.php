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

namespace ErlMutex\Adapter;

use ErlMutex\Exception\Exception;
use ErlMutex\LoggerInterface;
use ErlMutex\Service\Profiler;

/**
 * Адаптер для работы через socket
 *
 * Class Socket
 * @package ErlMutex\Adapter
 */
class Socket implements AdapterInterface
{
    const DEFAULT_HOST   = '127.0.0.1';
    const DEFAULT_PORT   = 7007;

    const ACTION_GET     = 'get';
    const ACTION_ACQUIRE = 'acquire';
    const ACTION_RELEASE = 'release';

    /**
     * @var string
     */
    private $hostname;

    /**
     * @var int
     */
    private $port;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param string $hostname Хост сервиса блокировок
     * @param int    $port     Порт сервиса блокировок (по умолчанию 7007)
     *
     * @throws Exception
     */
    public function __construct($hostname=self::DEFAULT_HOST, $port=self::DEFAULT_PORT)
    {
        $this->hostname = $hostname;
        $this->port     = $port;
    }
}