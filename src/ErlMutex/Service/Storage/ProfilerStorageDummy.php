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

namespace ErlMutex\Service\Storage;

use ErlMutex\ProfilerStorageInterface;
use ErlMutex\Entity\Profiler\Stack as ProfilerStackModel;

/**
 * Хранилище для отладки
 * Сделано синглтоном для unit test'ов
 *
 * Class ProfilerStorageDummy
 * @package erl\Service\Storage
 */
class ProfilerStorageDummy implements ProfilerStorageInterface
{
    /**
     * @var ProfilerStorageDummy
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $stack = array();

    /**
     * Constructor
     */
    private function __construct() {}

    /**
     * Для реализации синглтона еще нужно запретить клонирование и unserialize
     * Но здесь это не нужно
     *
     * private function __wakeup() {}
     * private function __clone() {}
     */

    /**
     * Получить эклемпляр хранилища
     *
     * @return ProfilerStorageDummy
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new ProfilerStorageDummy();
        }

        return self::$instance;
    }

    /**
     * Очистить хранилище
     *
     * @return bool
     */
    public function truncate()
    {
        $this->stack = array();
    }

    /**
     * Сохранить запись
     *
     * @param ProfilerStackModel $model
     * @return bool
     */
    public function insert(ProfilerStackModel $model)
    {
        $this->stack[] = $model;
    }

    /**
     * Получить список записей
     *
     * @return array
     */
    public function getList()
    {
        return $this->stack;
    }
} 