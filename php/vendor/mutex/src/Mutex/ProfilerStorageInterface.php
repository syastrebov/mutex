<?php

/**
 * PHP-Erlang mutex
 * Сервис блокировок для обработки критических секций
 *
 * @category mutex
 * @package  mutex
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/mutex
 */

namespace Mutex;

use Mutex\Model\ProfileStack as ProfileStackModel;

/**
 * Хранилище карты вызова блокировок
 *
 * Interface StorageInterface
 * @package mutex
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