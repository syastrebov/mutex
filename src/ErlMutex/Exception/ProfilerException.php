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

namespace ErlMutex\Exception;

use ErlMutex\Model\ProfilerStack as ProfilerStackModel;

/**
 * Class ProfilerException
 * @package erl\Exception
 */
class ProfilerException extends \Exception
{
    /**
     * @var ProfilerStackModel
     */
    private $profilerStackModel;

    /**
     * @param ProfilerStackModel $profilerStackModel
     * @return $this
     */
    public function setProfilerStackModel(ProfilerStackModel $profilerStackModel)
    {
        $this->profilerStackModel = $profilerStackModel;
        return $this;
    }

    /**
     * @return ProfilerStackModel
     */
    public function getProfilerStackModel()
    {
        return $this->profilerStackModel;
    }
}