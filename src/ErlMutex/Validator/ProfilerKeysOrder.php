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
 * Проверка правильного вызова ключей
 *
 * Исключение ситуаций типа (схема вызова):
 *
 * <A>
 *  <B>
 *  <B>
 * </A>
 *
 * Должно быть:
 *
 * <B>
 *  <A>
 *  </A>
 * </B>
 *
 * Class ProfilerKeysOrder
 * @package ErlMutex\Validator
 */
class ProfilerKeysOrder implements ProfilerValidatorInterface
{

}