<?php

include __DIR__ . '/../src/Mutex/Mutex.php';
include __DIR__ . '/../src/Base/Base.php';

$mutex = new Mutex('127.0.0.1', 7007);
$mutex->get('key1', false);

Base::msg('start');
if ($mutex->acquire()) {
    Base::msg('acquired');
    sleep(10);
    $mutex->release();
}
Base::msg('end');

$mutex->get('key1', false);
$mutex->get('key2', false);

if ($mutex->acquire('key1')) {
    Base::msg('acquired key1');
    if ($mutex->acquire('key2')) {
        Base::msg('acquired key2');
        $mutex->release();
    }
    $mutex->release();
}
Base::msg('end');


