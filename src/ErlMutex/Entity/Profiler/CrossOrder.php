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

namespace ErlMutex\Entity\Profiler;

use ErlMutex\Exception\ProfilerException;

/**
 * Модель состояния последовательности вызова для отладчика
 *
 * Class ProfilerCrossOrder
 * @package ErlMutex\Entity
 */
class CrossOrder
{
    /**
     * Ключ блокировки
     *
     * @var string
     */
    private $key;

    /**
     * Заблокирована ли модель
     *
     * @var bool
     */
    private $acquired = false;

    /**
     * Содержит ключи
     *
     * @var array
     */
    private $containKeys = array();

    /**
     * Constructor
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Ключ блокировки
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Заблокирована ли модель
     *
     * @return bool
     */
    public function isAcquired()
    {
        return $this->acquired;
    }

    /**
     * Заблокировать модель
     *
     * @throws ProfilerException
     */
    public function acquire()
    {
        if ($this->acquired) {
            throw new ProfilerException(sprintf('Модель `%s` уже заблокирована', $this->key));
        }

        $this->acquired = true;
    }

    /**
     * Снять блокировку
     *
     * @throws ProfilerException
     */
    public function release()
    {
        if (!$this->acquired) {
            throw new ProfilerException(sprintf('Модель `%s` не была заблокирована', $this->key));
        }

        $this->acquired = false;
    }

    /**
     * Есть ли вложенный ключ
     *
     * @param string $key
     * @return bool
     */
    public function hasContainKey($key)
    {
        return in_array($key, $this->containKeys);
    }

    /**
     * Есть вложенные ключи
     *
     * @return bool
     */
    public function hasContainKeys()
    {
        return !empty($this->containKeys);
    }

    /**
     * Добавить вложенный ключ
     *
     * @param string $key
     * @throws ProfilerException
     */
    public function addContainKey($key)
    {
        if ($this->hasContainKey($key)) {
            throw new ProfilerException(sprintf('Модель уже содержит ключ `%s`', $key));
        }

        $this->containKeys[] = $key;
    }

    /**
     * Удалить вложенный ключ
     *
     * @param string $key
     */
    public function removeContainKey($key)
    {
        foreach ($this->containKeys as $num => $containsKey) {
            if ($key === $containsKey) {
                unset($this->containKeys[$num]);
            }
        }
    }
} 