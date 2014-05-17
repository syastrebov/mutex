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
 * Проверка перехлестных вызовов блокировок
 *
 * Исключение ситуаций типа:
 *  - get A
 *  - get B
 *  - acquire A
 *  - acquire B
 *  - release A
 *  - release B
 *
 * Схема вызова:
 *
 * <A>
 *  <B>
 *  </A>
 * </B>
 *
 * Должно быть:
 *
 * <A>
 *  <B>
 *  </B>
 * </A>
 *
 * Class ProfilerCrossOrder
 * @package ErlMutex\Validator
 */
class ProfilerCrossOrder implements ProfilerValidatorInterface
{

}