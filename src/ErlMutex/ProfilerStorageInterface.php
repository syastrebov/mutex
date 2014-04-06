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

namespace ErlMutex;

use ErlMutex\Model\ProfilerStack as ProfilerStackModel;

/**
 * Хранилище карты вызова блокировок
 *
 * Interface StorageInterface
 * @package erl
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
     * @param ProfilerStackModel $model
     * @return bool
     */
    public function insert(ProfilerStackModel $model);

    /**
     * Получить список записей
     *
     * @return array
     */
    public function getList();
}