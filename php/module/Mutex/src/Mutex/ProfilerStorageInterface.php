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

namespace Mutex;

use Mutex\Model\ProfileStack as ProfileStackModel;

/**
 * Хранилище карты вызова блокировок
 *
 * Interface StorageInterface
 * @package Mutex
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
     * @param string            $requestUri
     * @param ProfileStackModel $model
     *
     * @return bool
     */
    public function insert($requestUri, ProfileStackModel $model);

    /**
     * Получить список записей
     *
     * @return array
     */
    public function getList();
}