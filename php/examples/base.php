<?php

include __DIR__ . '/../config/bootstrap.php';

use Mutex\Service\Mutex;
use Mutex\Service\Profiler;
use Mutex\Exception\Exception;

try {
    $profiler = new Profiler(__FILE__);
    Profiler::debugMessage($profiler->getRequestUri());

    $mutex = new Mutex('127.0.0.1', 7007);
    $mutex->setProfiler($profiler);
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
    $profiler->dump();

} catch (Exception $e) {
    Profiler::debugMessage($e->getMessage());
}


