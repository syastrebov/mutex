<?php

include __DIR__ . '/../config/bootstrap.php';

use Mutex\Service\Mutex;
use Mutex\Service\Profiler;
use Mutex\Exception\Exception;

try {
    $profiler = new Profiler(__FILE__);
    Profiler::debugMessage($profiler->getRequestUri());

    $mutex = new Mutex('127.0.0.1', 7007);
    $mutex->establishConnection()->setProfiler($profiler);
    if (!$mutex->isAlive()) {
        throw new Exception('Не удалось подключиться к сервису');
    }

    $mutex->get('key1', false);

    if ($mutex->acquire()) {
        sleep(10);
        $mutex->release();
    }

    $mutex->get('key1', false);
    $mutex->get('key2', false);

    if ($mutex->acquire('key1')) {
        if ($mutex->acquire('key2')) {
            $mutex->release();
        }
        $mutex->release();
    }

    $profiler->dump();

} catch (Exception $e) {
    Profiler::debugMessage($e->getMessage());
}


