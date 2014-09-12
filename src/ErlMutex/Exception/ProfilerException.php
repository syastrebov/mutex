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

namespace ErlMutex\Exception;

use ErlMutex\Entity\Profiler\Stack as ProfilerStackModel;

/**
 * Исключение для профайлера
 *
 * Class ProfilerException
 * @package erl\Exception
 */
class ProfilerException extends \Exception
{
    /**
     * Модель зарпоса
     *
     * @var ProfilerStackModel
     */
    private $profilerStackModel;

    /**
     * @var array
     */
    private $description;

    /**
     * Установить на какой модели запроса произошла ошибка
     *
     * @param ProfilerStackModel $profilerStackModel
     * @return $this
     */
    public function setProfilerStackModel(ProfilerStackModel $profilerStackModel)
    {
        $this->profilerStackModel = $profilerStackModel;
        return $this;
    }

    /**
     * Связанная модель
     *
     * @return ProfilerStackModel
     */
    public function getProfilerStackModel()
    {
        return $this->profilerStackModel;
    }

    /**
     * Описание исключения
     *
     * @param array $description
     * @return $this
     */
    public function setDescription(array $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Описание исключения
     *
     * @return array
     */
    public function getDescription()
    {
        return $this->description;
    }
}