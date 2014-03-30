mutex
=====

PHP-Erlang Mutex

Сервис блокировок для обработки критических секций в php коде.
Особенности:
 - Поддержка таймаутов блокировок
 - Блокировка по строковому ключу

=====

Примеры использования:


    $mutex = new Mutex('127.0.0.1', 7007);
    $mutex->->establishConnection();
    $mutex->get('key1', false);
    
    if ($mutex->acquire()) {
        // критическая секция
        // …
        
        $mutex->release();
    }

Очередь блокировок:

    $mutex->get('key1', false);
    $mutex->get('key2', false);
    
    if ($mutex->acquire('key1')) {
        if ($mutex->acquire('key2')) {
            $mutex->release('key2');
        }
        $mutex->release('key1');
    }
