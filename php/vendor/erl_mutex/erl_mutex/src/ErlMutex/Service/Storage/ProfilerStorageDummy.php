<?php

/**
 * PHP-Erlang erl_mutex
 * Сервис блокировок для обработки критических секций
 *
 * @category erl_mutex
 * @package  erl_mutex
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/erl_mutex
 */

namespace ErlMutex\Service\Storage;

use ErlMutex\ProfilerStorageInterface;
use ErlMutex\Model\ProfileStack as ProfileStackModel;

/**
 * Хранилище для отладки
 * Сделано синглтоном для unit test'ов
 *
 * Class ProfilerStorageDummy
 * @package erl_mutex\Service\Storage
 */
class ProfilerStorageDummy implements ProfilerStorageInterface
{
    /**
     * @var ProfilerStorageDummy
     */
    private static $_instance = null;

    /**
     * @var array
     */
    private $_stack = array();

    /**
     * Constructor
     */
    private function __construct() {}

    /**
     * Запрещаем unserialize
     */
    private function __wakeup() {}

    /**
     * Запрещаем клонирование
     */
    private function __clone() {}

    /**
     * Получить эклемпляр хранилища
     *
     * @return ProfilerStorageDummy
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new ProfilerStorageDummy();
        }

        return self::$_instance;
    }

    /**
     * Очистить хранилище
     *
     * @return bool
     */
    public function truncate()
    {
        $this->_stack = array();
    }

    /**
     * Сохранить запись
     *
     * @param ProfileStackModel $model
     * @return bool
     */
    public function insert(ProfileStackModel $model)
    {
        $this->_stack[] = $model;
    }

    /**
     * Получить список записей
     *
     * @return array
     */
    public function getList()
    {
        return $this->_stack;
    }
} 