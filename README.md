mutex
=====

PHP-Erlang Mutex

Сервис блокировок для обработки критических секций в php коде.
Особенности:
 - Поддержка таймаутов блокировок
 - Блокировка по строковому ключу

=====

Пример использования:

$mutex = new Mutex('127.0.0.1', 7007);
$mutex->->establishConnection();
$mutex->get('key1', false);

if ($mutex->acquire()) {
    // критическая секция
    // …
    
    $mutex->release();
}

