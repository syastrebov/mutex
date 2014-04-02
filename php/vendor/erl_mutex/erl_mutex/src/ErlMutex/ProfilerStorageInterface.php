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

namespace ErlMutex;

use ErlMutex\Model\ProfileStack as ProfileStackModel;

/**
 * Хранилище карты вызова блокировок
 *
 * Interface StorageInterface
 * @package erl_mutex
 */
interface ProfilerStorageInterface
{
    /**
     * Очистить хранилище
     *
     * @return bool
     */
    public function truncate();

    /**
     * Сохранить запись
     *
     * @param ProfileStackModel $model
     * @return bool
     */
    public function insert(ProfileStackModel $model);

    /**
     * Получить список записей
     *
     * @return array
     */
    public function getList();
}