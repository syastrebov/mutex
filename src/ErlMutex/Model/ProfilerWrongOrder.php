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

namespace ErlMutex\Model;

use ErlMutex\Exception\ProfilerException;
use ErlMutex\Model\ProfilerStack as ProfilerStackModel;

/**
 * Модель профайлера для определения приоритета правильности последовательности
 *
 * Class ProfilerBaseOrder
 * @package ErlMutex\Model
 */
class ProfilerWrongOrder extends ProfilerCrossOrder
{
    /**
     * @var ProfilerStackModel
     */
    private $trace;

    /**
     * Доступные вложенные ключи
     *
     * @var array
     */
    private $canContains = array();

    /**
     * Constructor
     *
     * @param ProfilerStackModel $trace
     */
    public function __construct(ProfilerStackModel $trace)
    {
        parent::__construct($trace->getKey());
        $this->trace = $trace;
    }

    /**
     * Добавить вложенный ключ
     *
     * @param string $key
     * @throws ProfilerException
     */
    public function addContainKey($key)
    {
        parent::addContainKey($key);

        if (!in_array($key, $this->canContains)) {
            $this->canContains[] = $key;
        }
    }

    /**
     * Допустимая вложенность
     *
     * @return array
     */
    public function canContainKeys()
    {
        return $this->canContains;
    }

    /**
     * Этот ключ может быть вложенным
     *
     * @param string $key
     * @return bool
     */
    public function canContainKey($key)
    {
        return in_array($key, $this->canContains);
    }

    /**
     * Связанная модель лога
     *
     * @return ProfilerStackModel
     */
    public function getTrace()
    {
        return $this->trace;
    }
}