<?php

include __DIR__ . '/../src/Mutex/Mutex.php';
include __DIR__ . '/../src/Mutex/Profiler.php';

use Mutex\Mutex;
use Mutex\Profiler;

$mutex = new Mutex('127.0.0.1', 7007);
$mutex->get('key1', false);

Profiler::debugMessage('start');
if ($mutex->acquire()) {
    Profiler::debugMessage('acquired');
    sleep(10);
    $mutex->release();
}
Profiler::debugMessage('end');

$mutex->get('key1', false);
$mutex->get('key2', false);

if ($mutex->acquire('key1')) {
    Profiler::debugMessage('acquired key1');
    if ($mutex->acquire('key2')) {
        Profiler::debugMessage('acquired key2');
        $mutex->release();
    }
    $mutex->release();
}
Profiler::debugMessage('end');


