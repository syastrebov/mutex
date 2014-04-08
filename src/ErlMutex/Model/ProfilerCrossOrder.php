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

/**
 * Модель состояния последовательности вызова для отладчика
 *
 * Class ProfilerCrossOrder
 * @package ErlMutex\Model
 */
class ProfilerCrossOrder
{
    /**
     * @var string
     */
    private $_key;

    /**
     * @var bool
     */
    private $_acquired = false;

    /**
     * @var array
     */
    private $_containKeys = array();

    /**
     * Constructor
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->_key = $key;
    }

    /**
     * Ключ блокировки
     *
     * @return string
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * Заблокирована ли модель
     *
     * @return bool
     */
    public function isAcquired()
    {
        return $this->_acquired;
    }

    /**
     * Заблокировать модель
     *
     * @throws ProfilerException
     */
    public function acquire()
    {
        if ($this->_acquired) {
            throw new ProfilerException(sprintf('Модель `%s` уже заблокирована', $this->_key));
        }

        $this->_acquired = true;
    }

    /**
     * Снять блокировку
     *
     * @throws ProfilerException
     */
    public function release()
    {
        if (!$this->_acquired) {
            throw new ProfilerException(sprintf('Модель `%s` не была заблокирована', $this->_key));
        }

        $this->_acquired = false;
    }

    /**
     * Есть ли вложенный ключ
     *
     * @param string $key
     * @return bool
     */
    public function containKeys($key)
    {
        return in_array($key, $this->_containKeys);
    }

    /**
     * Есть вложенные ключи
     *
     * @return bool
     */
    public function hasContainsKeys()
    {
        return !empty($this->_containKeys);
    }

    /**
     * Добавить вложенный ключ
     *
     * @param string $key
     * @throws ProfilerException
     */
    public function addContainsKey($key)
    {
        if ($this->containKeys($key)) {
            throw new ProfilerException(sprintf('Модель уже содержит ключ `%s`', $key));
        }

        $this->_containKeys[] = $key;
    }

    /**
     * Удалить вложенный ключ
     *
     * @param string $key
     */
    public function removeContainsKey($key)
    {
        foreach ($this->_containKeys as $num => $containsKey) {
            if ($key === $containsKey) {
                unset($this->_containKeys[$num]);
            }
        }
    }
} 