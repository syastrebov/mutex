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

namespace ErlMutex\Validator;

use ErlMutex\Model\AbstractCollection;
use ErlMutex\ProfilerValidatorInterface;
use ErlMutex\Exception\ProfilerException as Exception;

/**
 * Коллекция валидаторов для профайлера
 *
 * Class ProfilerCollection
 * @package ErlMutex\Validator
 */
class ProfilerCollection extends AbstractCollection
{
    /**
     * Добавить валидатор в коллекцию
     *
     * @param ProfilerValidatorInterface $validator
     * @return $this
     * @throws Exception
     */
    public function append(ProfilerValidatorInterface $validator)
    {
        /** @var ProfilerValidatorInterface $existValidator */
        foreach ($this->collection as $existValidator) {
            if ($existValidator == $validator) {
                throw new Exception('Такой валидатор уже был добавлен');
            }
        }

        $this->collection[] = $validator;
        return $this;
    }

    /**
     * Получить коллекцию валидаторов
     *
     * @return ProfilerCollection
     */
    public static function getInstance()
    {
        $collection = new ProfilerCollection();
        $collection
            ->append(new ProfilerActionsOrder())
            ->append(new ProfilerCrossOrder())
            ->append(new ProfilerKeysOrder());

        return $collection;
    }
} 