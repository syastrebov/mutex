<?php

/**
 *
 * thread 1:
 *
 * lock A
 * lock B
 *
 * thread 2:
 *
 * lock B
 * lock A
 *
 */

include __DIR__ . '/../src/Mutex/Mutex.php';
include __DIR__ . '/../src/Mutex/Profiler.php';

use Mutex\Mutex;
use Mutex\Profiler;

$mutex = new Mutex('127.0.0.1', 7007);

$mutex->get('A', false);
$mutex->get('B', false);

if (isset($argv[1])) {
    if ($mutex->acquire('A')) {
        Profiler::msg('acquired A');
        sleep(10);

        if ($mutex->acquire('B')) {
            sleep(10);

            Profiler::msg('acquired B');
            $mutex->release('B');
        }
        $mutex->release('A');
    }
    Profiler::msg('end');
} else {
    if ($mutex->acquire('B')) {
        Profiler::msg('acquired B');
        sleep(10);

        if ($mutex->acquire('A')) {
            sleep(10);

            Profiler::msg('acquired A');
            $mutex->release('A');
        }
        $mutex->release('B');
    }
    Profiler::msg('end');
}