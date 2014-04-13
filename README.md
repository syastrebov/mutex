mutex
=====

PHP-Erlang Mutex

Сервис блокировок для обработки критических секций в php коде.
Особенности:
 - Поддержка таймаутов блокировок
 - Блокировка по строковому ключу
 - Профайлер правильности вызова очереди (отслеживание взаимных блокировок)

=====

##Примеры использования##

Базовое использование:

```php
$mutex = new Mutex('127.0.0.1', 7007);
$mutex->establishConnection();
$mutex->get('key1', false);

if ($mutex->acquire()) {
    // критическая секция
    // …
    
    $mutex->release();
}
```

Очередь блокировок:

```php
$mutex->get('key1', false);
$mutex->get('key2', false);

if ($mutex->acquire('key1')) {
    if ($mutex->acquire('key2')) {
        $mutex->release('key2');
    }
    $mutex->release('key1');
}
```

=====

##Установка##

```json
{
    "require": {
        "php": ">=5.2.4",
        "twig/twig": "1.*",
        "leafo/lessphp": "0.4.0",
        "erl/mutex": "0.1.0"
    },
    "repositories": [{
        "type": "package",
        "package": {
            "name": "erl/mutex",
            "version": "0.1.0",
            "source": {
                "url": "https://github.com/syastrebov/mutex.git",
                "type": "git",
                "reference": "master"
            },
            "autoload": {
                "psr-0": {
                    "ErlMutex\\": "src"
                }
            }
        }
    }]
}
```
