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

namespace ErlMutex\Validator;

use ErlMutex\ProfilerValidatorInterface;

/**
 * Проверка последовательности вызова блокировок по ключу
 *
 * Правильная последовательность:
 *  - get(Key)
 *  - acquire(Key)
 *  - release(Key)
 * Если последовательность не совпадает, возвращает исключение
 *
 * Class ProfilerActionsOrder
 * @package ErlMutex\Validator
 */
class ProfilerActionsOrder implements ProfilerValidatorInterface
{

}